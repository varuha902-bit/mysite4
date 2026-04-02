jQuery(function($) {
    // Namespace our attendee list functionality
    window.EVGE = window.EVGE || {};
    window.EVGE.AttendeeList = window.EVGE.AttendeeList || {};

    // Attendee list class
    window.EVGE.AttendeeList.Element = class {
        constructor($list) {
            this.$list = $list;
            this.$link = $list.find('.evge-load-more-link');
            this.$gridWrapper = $list.find('.evge-attendee-list-grid-wrapper');
            this.$headerInner = $list.find('.evge-attendee-list-grid-header-inner');
            this.$body = $list.find('.evge-attendee-list-grid-body');
            this.$navPrev = $list.find('.evge-grid-nav-prev');
            this.$navNext = $list.find('.evge-grid-nav-next');
            this.currentSort = {
                field: null,
                direction: null
            };
            this.visibleColumnStart = 0; // Track which column index we start showing from
            this.visibleColumnCount = 0; // Track how many columns are visible
            this.columnWidths = []; // Store calculated column widths for grid template
            this.events = {};
            this.init();
        }

        init() {
            this.initEventHandlers();
            $(document).on('click', '.evge-load-more-link', this.events.loadMore);
            
            // Initialize grid features for full layout
            if (this.$gridWrapper.length) {
                this.initGridFeatures();
            }
        }

        initEventHandlers() {
            // Group all event handlers in one place
            this.events = {
                loadMore: this.handleLoadMore.bind(this),
                navPrev: this.handleNavPrev.bind(this),
                navNext: this.handleNavNext.bind(this),
                sort: this.handleSort.bind(this)
            };
            
            // Debounced resize handler - waits 500ms after resize stops
            this.resizeTimeout = null;

            // Bind events
            this.bindEvents();
        }

        bindEvents() {
            // Load more button click
            if (this.$link.length) {
                this.$link.on('click', this.events.loadMore);
            }
            
            // Grid navigation (click and keyboard support for accessibility)
            if (this.$navPrev.length) {
                this.$navPrev.on('click', this.events.navPrev);
                this.$navPrev.on('keydown', (e) => {
                    // Support Enter and Space keys for accessibility
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.events.navPrev(e);
                    }
                });
            }
            if (this.$navNext.length) {
                this.$navNext.on('click', this.events.navNext);
                this.$navNext.on('keydown', (e) => {
                    // Support Enter and Space keys for accessibility
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.events.navNext(e);
                    }
                });
            }
            
            // Sort links (click and keyboard support)
            this.$list.find('.evge-grid-header-sort').on('click', this.events.sort);
            this.$list.find('.evge-grid-header-sort').on('keydown', (e) => {
                // Support Enter and Space keys for accessibility
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.events.sort(e);
                }
            });
            
            // Resize event for responsive adjustments - debounced to 500ms after resize stops
            const self = this;
            $(window).on('resize', function() {
                // Clear any pending resize handler
                if (self.resizeTimeout) {
                    clearTimeout(self.resizeTimeout);
                }
                
                // Set new timeout - will trigger after 500ms of no resize events
                self.resizeTimeout = setTimeout(function() {
                    self.handleResize();
                    self.resizeTimeout = null;
                }, 500);
            });
        }

        unbindEvents() {
            // Clean up event handlers
            if (this.$link && this.$link.length) {
                this.$link.off('click', this.events.loadMore);
            }
            if (this.$navPrev && this.$navPrev.length) {
                this.$navPrev.off('click', this.events.navPrev);
                this.$navPrev.off('keydown');
            }
            if (this.$navNext && this.$navNext.length) {
                this.$navNext.off('click', this.events.navNext);
                this.$navNext.off('keydown');
            }
            const $sortLinks = this.$list.find('.evge-grid-header-sort');
            $sortLinks.off('click', this.events.sort);
            $sortLinks.off('keydown');
            $(window).off('resize');
            
            // Clear any pending resize timeout
            if (this.resizeTimeout) {
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = null;
            }
        }

        initGridFeatures() {
            // Set CSS variable for column count
            const columnCount = this.$list.find('.evge-grid-header-cell').length;
            this.$gridWrapper.css('--evge-grid-columns', columnCount);
            
            // Check if container is narrow and apply class
            this.checkNarrowLayout();
            
            // Sync column widths between header and body
            // syncColumnWidths will call checkNavigationNeeded after widths are set
            this.syncColumnWidths();
            
            // Calculate and set initial visible columns
            const self = this;
            setTimeout(() => {
                self.calculateVisibleColumns();
                self.updateVisibleColumns();
                // Sync translated labels if in narrow view (updateVisibleColumns handles this, but ensure it's done)
                if (self.$list.hasClass('evge-attendee-list-narrow')) {
                    self.syncTranslatedLabels();
                }
                self.checkNavigationNeeded();
                self.updateNavigationButtons();
            }, 100);
        }

        /**
         * Build grid-template-columns string with flexible sizing when there's extra space
         * @param {Array<number>} columnWidths - Array of column widths in pixels
         * @param {number} containerWidth - Available container width
         * @returns {string} Grid template columns string
         */
        buildGridTemplateColumns(columnWidths, containerWidth) {
            if (!columnWidths || columnWidths.length === 0) {
                return '';
            }
            
            // Calculate total width needed for all columns
            const totalColumnsWidth = columnWidths.reduce((sum, width) => sum + width, 0);
            const minColumnWidth = 100; // Minimum column width to maintain readability
            
            // If total width is less than container width, use flexible sizing to fill space
            if (totalColumnsWidth < containerWidth && containerWidth > 0) {
                // Use minmax to allow columns to grow proportionally while maintaining minimum width
                // This distributes extra space evenly across columns
                return columnWidths.map(width => `minmax(${width}px, 1fr)`).join(' ');
            } else if (totalColumnsWidth > containerWidth && containerWidth > 0) {
                // When columns exceed container width, scale them down proportionally
                // Calculate scale factor to fit within container
                const scaleFactor = containerWidth / totalColumnsWidth;
                
                // Scale down columns proportionally, ensuring minimum width
                const scaledWidths = columnWidths.map(width => {
                    const scaled = width * scaleFactor;
                    // Ensure scaled width is at least the minimum
                    return Math.max(scaled, minColumnWidth);
                });
                
                // Recalculate total after applying minimums
                const newTotal = scaledWidths.reduce((sum, width) => sum + width, 0);
                
                // If scaled widths with minimums still exceed container, use equal distribution with minmax
                if (newTotal > containerWidth) {
                    // Calculate max width per column to ensure fit
                    const maxWidthPerColumn = Math.floor((containerWidth - 2) / columnWidths.length);
                    // Use minmax with minimum width and constrained maximum
                    return columnWidths.map(() => {
                        return `minmax(${minColumnWidth}px, ${maxWidthPerColumn}px)`;
                    }).join(' ');
                }
                
                // Use the scaled widths as fixed sizes
                return scaledWidths.map(width => Math.floor(width) + 'px').join(' ');
            } else {
                // Use fixed pixel widths when widths match container or container is 0
                return columnWidths.map(width => width + 'px').join(' ');
            }
        }

        syncColumnWidths() {
            if (!this.$headerInner.length || !this.$body.length) return;
            
            // In narrow view, don't set explicit widths - let CSS handle it
            const isNarrow = this.$list.hasClass('evge-attendee-list-narrow');
            if (isNarrow) {
                // Clear any previously set grid template columns in narrow view
                this.$headerInner.css('--evge-grid-template-columns', '');
                this.$body.find('.evge-attendee-row').css('--evge-grid-template-columns', '');
                return;
            }
            
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const $allRows = this.$body.find('.evge-attendee-row');
            
            if (!$allRows.length) return;
            
            // Temporarily set grid to auto to measure natural content widths
            const tempGridTemplate = 'repeat(' + $headerCells.length + ', max-content)';
            this.$headerInner.css('--evge-grid-template-columns', tempGridTemplate);
            $allRows.css('--evge-grid-template-columns', tempGridTemplate);
            
            // Force a reflow to ensure grid is applied and measured
            this.$headerInner[0].offsetHeight;
            if ($allRows.length) {
                $allRows[0].offsetHeight;
            }
            
            // Calculate the maximum width needed for each column
            const columnWidths = [];
            
            $headerCells.each(function(index) {
                const $headerCell = $(this);
                // Get the actual rendered width from the grid
                // getBoundingClientRect().width includes padding and border (border-box)
                // This is the correct value to use for grid-template-columns
                const headerRect = $headerCell[0].getBoundingClientRect();
                let maxWidth = headerRect.width;
                
                // Check all body cells in this column
                $allRows.each(function() {
                    const $row = $(this);
                    const $cell = $row.find('.evge-grid-cell').eq(index);
                    if ($cell.length) {
                        // Get the actual rendered width from the grid
                        // This includes padding and border (border-box sizing)
                        const cellRect = $cell[0].getBoundingClientRect();
                        const cellWidth = cellRect.width;
                        maxWidth = Math.max(maxWidth, cellWidth);
                    }
                });
                
                // Ensure minimum width, and round to avoid sub-pixel issues
                // The width we store should match what we'll use in grid-template-columns
                columnWidths[index] = Math.ceil(Math.max(maxWidth, 150));
            });
            
            // Store column widths for later use
            this.columnWidths = columnWidths;
            
            // Get container width (accounting for any padding/borders)
            const containerWidth = this.$headerInner[0].clientWidth;
            
            // Build grid-template-columns string with flexible sizing when appropriate
            const gridTemplateColumns = this.buildGridTemplateColumns(columnWidths, containerWidth);
            
            // Apply the same grid-template-columns to both header and all rows
            this.$headerInner.css('--evge-grid-template-columns', gridTemplateColumns);
            $allRows.css('--evge-grid-template-columns', gridTemplateColumns);
            
            // Force a reflow to ensure grid is updated
            this.$headerInner[0].offsetHeight;
            
            // After syncing widths, check if navigation is needed
            // Use setTimeout to ensure DOM has updated
            const self = this;
            setTimeout(() => {
                self.checkNavigationNeeded();
            }, 10);
        }

        calculateVisibleColumns() {
            if (!this.$headerInner.length) return;
            
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const totalColumns = $headerCells.length;
            const containerWidth = this.$headerInner[0].clientWidth;
            
            if (totalColumns === 0) {
                this.visibleColumnCount = 0;
                return;
            }
            
            // Use stored column widths if available (these include padding via border-box sizing)
            // Otherwise, measure current widths
            let columnWidths = this.columnWidths;
            if (!columnWidths || columnWidths.length !== totalColumns) {
                // Fallback: measure current widths
                columnWidths = [];
                $headerCells.each(function() {
                    // Use getBoundingClientRect().width to match how we store widths
                    // This includes padding and border (border-box sizing)
                    const rect = this.getBoundingClientRect();
                    columnWidths.push(rect.width);
                });
            }
            
            // Calculate how many columns can fit
            let totalWidth = 0;
            let visibleCount = 0;
            
            for (let i = 0; i < totalColumns; i++) {
                const cellWidth = columnWidths[i] || 150; // Fallback to minimum
                if (totalWidth + cellWidth <= containerWidth) {
                    totalWidth += cellWidth;
                    visibleCount++;
                } else {
                    break; // Stop when we can't fit more
                }
            }
            
            this.visibleColumnCount = Math.max(1, visibleCount); // At least show 1 column
        }

        checkNarrowLayout() {
            if (!this.$gridWrapper.length) return;
            
            const containerWidth = this.$gridWrapper[0].clientWidth;
            const narrowThreshold = 600; // Same as the old media query breakpoint
            const wasNarrow = this.$list.hasClass('evge-attendee-list-narrow');
            
            if (containerWidth < narrowThreshold) {
                this.$list.addClass('evge-attendee-list-narrow');
                // If we just entered narrow view, sync translated labels
                if (!wasNarrow) {
                    this.syncTranslatedLabels();
                }
            } else {
                this.$list.removeClass('evge-attendee-list-narrow');
            }
        }

        /**
         * Sync translated labels from header cells to body cell data attributes
         * This ensures labels are properly translated in narrow view
         * 
         * Security: .text() strips HTML tags, and jQuery's .attr() HTML-encodes the value
         */
        syncTranslatedLabels() {
            if (!this.$headerInner.length || !this.$body.length) return;
            
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const $allRows = this.$body.find('.evge-attendee-row');
            
            // Create a map of field slugs to translated labels from header
            const labelMap = {};
            $headerCells.each(function() {
                const $headerCell = $(this);
                const fieldSlug = $headerCell.data('field-slug');
                
                // Validate fieldSlug is a string
                if (typeof fieldSlug !== 'string' || !fieldSlug) {
                    return;
                }
                
                // Get the translated label text from the header cell
                // .text() strips HTML tags and returns plain text
                const $labelSpan = $headerCell.find('.evge-grid-header-label');
                if ($labelSpan.length) {
                    const labelText = $labelSpan.text().trim();
                    if (labelText) {
                        labelMap[fieldSlug] = labelText;
                    }
                }
            });
            
            // Update data-field-label attributes on all body cells with translated labels
            if (Object.keys(labelMap).length > 0) {
                $allRows.each(function() {
                    const $row = $(this);
                    $row.find('.evge-grid-cell').each(function() {
                        const $cell = $(this);
                        const fieldSlug = $cell.data('field-slug');
                        
                        // jQuery's .attr() will HTML-encode the value automatically
                        if (typeof fieldSlug === 'string' && fieldSlug && labelMap[fieldSlug]) {
                            $cell.attr('data-field-label', labelMap[fieldSlug]);
                        }
                    });
                });
            }
        }

        updateVisibleColumns() {
            if (!this.$headerInner.length || !this.$body.length) return;
            
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const $allRows = this.$body.find('.evge-attendee-row');
            const totalColumns = $headerCells.length;
            
            // Check if we're in narrow layout - if so, show all columns
            const isNarrow = this.$list.hasClass('evge-attendee-list-narrow');

            
            if (isNarrow) {
                // In narrow view, show all columns
                $headerCells.show();
                $allRows.each(function() {
                    const $row = $(this);
                    $row.find('.evge-grid-cell').show();
                });
                
                // Sync translated labels from header to body cells
                this.syncTranslatedLabels();
                
                // Reset grid template to show all columns using stored widths or calculate
                const containerWidth = this.$headerInner[0].clientWidth;
                let allColumnsTemplate;
                
                if (this.columnWidths.length === totalColumns) {
                    allColumnsTemplate = this.buildGridTemplateColumns(this.columnWidths, containerWidth);
                } else {
                    // Fallback: calculate widths
                    const allWidths = [];
                    $headerCells.each(function() {
                        allWidths.push($(this).outerWidth());
                    });
                    allColumnsTemplate = this.buildGridTemplateColumns(allWidths, containerWidth);
                }
                
                this.$headerInner.css('--evge-grid-template-columns', allColumnsTemplate);
                $allRows.css('--evge-grid-template-columns', allColumnsTemplate);
            } else {
                // Normal view - use column visibility logic
                // Ensure visibleColumnStart is within valid range
                this.visibleColumnStart = Math.max(0, Math.min(this.visibleColumnStart, totalColumns - this.visibleColumnCount));

                // Build grid-template-columns for visible columns only using stored widths
                const visibleWidths = [];
                
                // Show/hide header cells and collect visible column widths
                $headerCells.each((index) => {
                    const $cell = $headerCells.eq(index);
                    if (index >= this.visibleColumnStart && index < this.visibleColumnStart + this.visibleColumnCount) {
                        $cell.show();
                        // Use stored width if available, otherwise get current width
                        const width = (this.columnWidths[index] !== undefined) ? this.columnWidths[index] : $cell.outerWidth();
                        visibleWidths.push(width);
                    } else {
                        $cell.hide();
                    }
                });
                
                // Update grid-template-columns to only include visible columns
                const containerWidth = this.$headerInner[0].clientWidth;
                const visibleTemplate = this.buildGridTemplateColumns(visibleWidths, containerWidth);
                this.$headerInner.css('--evge-grid-template-columns', visibleTemplate);
                
                // Show/hide body cells and update their grid template
                const self = this;
                $allRows.each(function() {
                    const $row = $(this);
                    const $cells = $row.find('.evge-grid-cell');
                    $cells.each((index) => {
                        const $cell = $cells.eq(index);
                        if (index >= self.visibleColumnStart && index < self.visibleColumnStart + self.visibleColumnCount) {
                            $cell.show();
                        } else {
                            $cell.hide();
                        }
                    });
                    // Update grid template for this row
                    $row.css('--evge-grid-template-columns', visibleTemplate);
                });
            }
        }

        checkNavigationNeeded() {
            if (!this.$headerInner.length) return;
            
            // In narrow view, hide navigation buttons
            const isNarrow = this.$list.hasClass('evge-attendee-list-narrow');
            if (isNarrow) {
                this.$navPrev.hide();
                this.$navNext.hide();
                return;
            }
            
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const totalColumns = $headerCells.length;
            
            // Check if we have more columns than can be displayed
            const needsNavigation = totalColumns > this.visibleColumnCount;
            
            if (needsNavigation) {
                // Show/hide buttons based on visible column position
                this.updateNavigationButtons();
            } else {
                this.$navPrev.hide();
                this.$navNext.hide();
            }
        }

        updateNavigationButtons() {
            if (!this.$headerInner.length) return;
            
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const totalColumns = $headerCells.length;
            
            // Hide/show prev button based on visible column start
            if (this.visibleColumnStart <= 0) {
                this.$navPrev.hide();
            } else {
                this.$navPrev.css('display', 'flex');
            }
            
            // Hide/show next button based on visible column end
            if (this.visibleColumnStart + this.visibleColumnCount >= totalColumns) {
                this.$navNext.hide();
            } else {
                this.$navNext.css('display', 'flex');
            }
        }

        handleNavPrev(e) {
            e.preventDefault();
            if (!this.$headerInner.length || !this.$body.length) return;
            
            // Move visible columns to the left (show previous columns)
            const columnsToMove = Math.max(1, Math.floor(this.visibleColumnCount * 0.75));
            this.visibleColumnStart = Math.max(0, this.visibleColumnStart - columnsToMove);
            
            // Update visible columns
            this.updateVisibleColumns();
            this.updateNavigationButtons();
        }

        handleNavNext(e) {
            e.preventDefault();
            if (!this.$headerInner.length || !this.$body.length) return;
            
            // Move visible columns to the right (show next columns)
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const totalColumns = $headerCells.length;
            const columnsToMove = Math.max(1, Math.floor(this.visibleColumnCount * 0.75));
            const maxStart = totalColumns - this.visibleColumnCount;
            
            this.visibleColumnStart = Math.min(maxStart, this.visibleColumnStart + columnsToMove);
            
            // Update visible columns
            this.updateVisibleColumns();
            this.updateNavigationButtons();
        }

        handleResize() {
            // Check if layout changed from narrow to wide or vice versa
            this.checkNarrowLayout();
            
            this.syncColumnWidths();
            this.calculateVisibleColumns();
            // Reset to start if current position is invalid
            const $headerCells = this.$headerInner.find('.evge-grid-header-cell');
            const totalColumns = $headerCells.length;
            if (this.visibleColumnStart + this.visibleColumnCount > totalColumns) {
                this.visibleColumnStart = Math.max(0, totalColumns - this.visibleColumnCount);
            }
            this.updateVisibleColumns();
            this.checkNavigationNeeded();
            this.updateNavigationButtons();
        }

        handleSort(e) {
            e.preventDefault();
            const $link = $(e.currentTarget);
            const fieldSlug = $link.data('field-slug');
            
            // Determine sort direction
            let direction = 'asc';
            if (this.currentSort.field === fieldSlug && this.currentSort.direction === 'asc') {
                direction = 'desc';
            }
            
            // Update sort state
            this.currentSort = {
                field: fieldSlug,
                direction: direction
            };
            
            // Update UI
            this.$list.find('.evge-grid-header-sort').removeAttr('data-sort');
            $link.attr('data-sort', direction);
            
            // Sort attendees
            this.sortAttendees(fieldSlug, direction);
        }

        sortAttendees(fieldSlug, direction) {
            // Show all attendees first when sorting is triggered
            const $hiddenAttendees = this.$list.find('.evge-attendee-hidden');
            if ($hiddenAttendees.length > 0) {
                // Remove hidden class and show immediately
                $hiddenAttendees.removeClass('evge-attendee-hidden').show();
                
                // Hide the "Show Full List" link since we're showing everything
                if (this.$link && this.$link.length) {
                    this.$link.parent().hide();
                }
            }
            
            const $rows = this.$list.find('.evge-attendee-row');
            const $container = this.$body;
            
            if (!$rows.length) return;
            
            // Get all rows with their data
            const rows = $rows.map(function() {
                const $row = $(this);
                const $cell = $row.find(`.evge-grid-cell[data-field-slug="${fieldSlug}"]`);
                
                if (!$cell.length) {
                    console.warn('EVGE Attendee List: Cell not found for field slug:', fieldSlug);
                    return null;
                }
                
                // Use attr() instead of data() for more reliable HTML5 data attribute access
                // Also fallback to text content if data attribute is not available
                let value = $cell.attr('data-field-value');
                if (!value || value === '') {
                    value = $cell.text().trim();
                }
                
                return {
                    element: $row[0],
                    value: value || '',
                    $row: $row
                };
            }).get().filter(row => row !== null); // Filter out any null entries
            
            // Sort rows
            rows.sort((a, b) => {
                let aVal = a.value;
                let bVal = b.value;
                
                // Handle empty values
                if (!aVal && !bVal) return 0;
                if (!aVal) return 1;
                if (!bVal) return -1;
                
                // Try to parse as numbers
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    aVal = aNum;
                    bVal = bNum;
                } else {
                    // String comparison
                    aVal = String(aVal).toLowerCase();
                    bVal = String(bVal).toLowerCase();
                }
                
                if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                return 0;
            });
            
            // Detach all rows first to avoid layout issues during reordering
            rows.forEach(row => {
                row.$row.detach();
            });
            
            // Re-append sorted rows
            rows.forEach(row => {
                $container.append(row.$row);
            });
            
        }

        handleLoadMore(e) {
            e.preventDefault();
            
            // Show hidden attendees for all layouts (no AJAX)
            // All attendees are loaded initially, we just reveal the hidden ones
            this.showHiddenAttendees();
        }

        showHiddenAttendees() {
            // Show all hidden attendees (no AJAX - all attendees are already loaded)
            const $hiddenAttendees = this.$list.find('.evge-attendee-hidden');
            
            if ($hiddenAttendees.length > 0) {
                const self = this;
                
                // Remove hidden class first
                $hiddenAttendees.removeClass('evge-attendee-hidden');
                
                // Animate the reveal
                $hiddenAttendees.slideDown(300, function() {
          
                });
                
                // Remove the load more link after showing all attendees
                if (this.$link && this.$link.length) {
                    this.$link.parent().fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }
        }

        destroy() {
            this.unbindEvents();
            this.$list = null;
            this.$link = null;
            this.$gridWrapper = null;
            this.$headerInner = null;
            this.$body = null;
            this.$navPrev = null;
            this.$navNext = null;
            this.events = null;
        }
    };

    // Attendee list initializer
    window.EVGE.AttendeeList.Initializer = {
        init: function() {
            if (!this.checkDependencies()) return;
            window.EVGE.Hooks.addAction('evge_page_created', this.handlePageCreated.bind(this));
            window.EVGE.Hooks.addAction('evge_page_destroyed', this.handlePageDestroyed.bind(this));
            window.EVGE.Hooks.addAction('evge_modal_content_loaded', this.handlePageCreated.bind(this));
        },

        checkDependencies: function() {
            if (typeof window.EVGE.Hooks === 'undefined') {
                console.error('EVGE Attendee List: Required dependencies not loaded');
                return false;
            }
            return true;
        },

        handlePageCreated: function() {
            this.instances = [];
            $('.evge-attendee-list').each((index, element) => {
                this.instances.push(new window.EVGE.AttendeeList.Element($(element)));
            });
        },

        handlePageDestroyed: function() {
            if (this.instances) {
                this.instances.forEach(instance => instance.destroy());
                this.instances = [];
            }
        }
    };

    // Initialize the attendee list functionality
    window.EVGE.AttendeeList.Initializer.init();
}); 