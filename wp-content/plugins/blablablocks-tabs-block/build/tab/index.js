/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@wordpress/icons/build-module/library/code.js":
/*!********************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/code.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * WordPress dependencies
 */


const code = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, {
    d: "M20.8 10.7l-4.3-4.3-1.1 1.1 4.3 4.3c.1.1.1.3 0 .4l-4.3 4.3 1.1 1.1 4.3-4.3c.7-.8.7-1.9 0-2.6zM4.2 11.8l4.3-4.3-1-1-4.3 4.3c-.7.7-.7 1.8 0 2.5l4.3 4.3 1.1-1.1-4.3-4.3c-.2-.1-.2-.3-.1-.4z"
  })
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (code);
//# sourceMappingURL=code.js.map

/***/ }),

/***/ "./node_modules/@wordpress/icons/build-module/library/reset.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@wordpress/icons/build-module/library/reset.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/primitives */ "@wordpress/primitives");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * WordPress dependencies
 */


const reset = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_0__.Path, {
    d: "M7 11.5h10V13H7z"
  })
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (reset);
//# sourceMappingURL=reset.js.map

/***/ }),

/***/ "./src/components/color-control.js":
/*!*****************************************!*\
  !*** ./src/components/color-control.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * WordPress dependencies.
 */




/**
 * Resolve a raw selection from ColorPalette against the provided
 * colorGradientSettings to see if it corresponds to a theme/preset color.
 *
 * @param {string|Object} rawColor
 * @param {Array}         colorGradientSettings - the array you get from useMultipleOriginColorsAndGradients()
 * @return {{ color: string|undefined, slug: string|undefined }} Object containing the selected color value and its slug if it matches a preset otherwise, both properties are undefined.
 */

function resolveColorSelection(rawColor, colorGradientSettings) {
  let pickedColor = '';
  if (typeof rawColor === 'object') {
    pickedColor = rawColor.color || rawColor;
  } else if (typeof rawColor === 'string') {
    pickedColor = rawColor;
  }
  if (!pickedColor) {
    return {
      color: undefined,
      slug: undefined
    };
  }
  const normalize = c => String(c).trim().toLowerCase();
  const target = normalize(pickedColor);
  const palettes = Array.isArray(colorGradientSettings?.colors) ? colorGradientSettings.colors : [];
  for (const palette of palettes) {
    if (!Array.isArray(palette.colors)) {
      continue;
    }
    for (const entry of palette.colors) {
      if (!entry || !entry.color) {
        continue;
      }
      if (normalize(entry.color) === target) {
        return {
          color: pickedColor,
          slug: entry.slug
        };
      }

      // crude handling for function-style colors like color-mix
      if (entry.color.includes('color-mix') && target.includes(entry.color.replace(/\s+/g, '').toLowerCase())) {
        return {
          color: pickedColor,
          slug: entry.slug
        };
      }
    }
  }
  return {
    color: pickedColor,
    slug: undefined
  };
}

/**
 * Renders a color control dropdown for selecting colors.
 *
 * @param {Object}   props               - The component props.
 * @param {string}   props.label         - The label for the color control.
 * @param {Object}   props.colorValue    - The current color values. Should include `default` and optionally `hover` (if `hasHover` is true).
 * @param {Function} props.onChangeColor - Callback function to handle color changes. Accepts an object with updated color values.
 * @param {boolean}  props.hasHover      - Determines if hover color support is enabled. If true, a tab for hover colors is displayed.
 * @param {boolean}  props.hasActive     - Determines if active color support is enabled. If true, a tab for active colors is displayed.
 *
 * @return {JSX.Element} The rendered ColorControlDropdown component.
 */
function ColorControlDropdown({
  label,
  colorValue = {},
  onChangeColor,
  hasHover = false,
  hasActive = false
}) {
  const colorGradientSettings = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.__experimentalUseMultipleOriginColorsAndGradients)();
  const handleChange = (tabName, rawColor) => {
    const normalized = resolveColorSelection(rawColor, colorGradientSettings);
    onChangeColor({
      ...colorValue,
      [tabName]: normalized
    });
  };
  const defaultIndicator = colorValue.default?.color || '';
  const hoverIndicator = hasHover ? colorValue.hover?.color : null;
  const activeIndicator = hasActive ? colorValue.active?.color : null;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Dropdown, {
    popoverProps: {
      placement: 'left-start',
      offset: 36,
      shift: true
    },
    contentClassName: "bbb-tabs_color_popover",
    renderToggle: ({
      isOpen,
      onToggle
    }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
      className: `bbb-tabs_color_button ${isOpen ? 'isOpen' : ''}`,
      "aria-expanded": isOpen,
      onClick: onToggle,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalHStack, {
        justify: "left",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalZStack, {
          offset: 10,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ColorIndicator, {
            colorValue: defaultIndicator
          }), hasHover && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ColorIndicator, {
            colorValue: hoverIndicator
          }), hasActive && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ColorIndicator, {
            colorValue: activeIndicator
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalText, {
          children: label
        })]
      })
    }),
    renderContent: () => hasHover || hasActive ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TabPanel, {
      tabs: [{
        name: 'default',
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Default', 'blablablocks-tabs-block')
      }, {
        name: 'hover',
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Hover', 'blablablocks-tabs-block')
      }, {
        name: 'active',
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Active', 'blablablocks-tabs-block')
      }],
      children: tab => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.ColorPalette, {
        __experimentalIsRenderedInSidebar: true,
        value: colorValue[tab.name]?.color || '',
        onChange: color => handleChange(tab.name, color),
        ...colorGradientSettings,
        enableAlpha: true
      })
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.ColorPalette, {
      className: "bbb-color-pallete-container",
      __experimentalIsRenderedInSidebar: true,
      value: colorValue.default?.color || '',
      onChange: color => {
        onChangeColor({
          ...colorValue,
          default: color
        });
      },
      ...colorGradientSettings,
      enableAlpha: true
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ColorControlDropdown);

/***/ }),

/***/ "./src/components/icon-picker/index.js":
/*!*********************************************!*\
  !*** ./src/components/icon-picker/index.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _modal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modal */ "./src/components/icon-picker/modal.js");
/* harmony import */ var _toolbar_button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./toolbar-button */ "./src/components/icon-picker/toolbar-button.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



/**
 * IconPicker Component
 *
 * A component that allows users to pick an SVG icon using a toolbar button
 * and modal interface. It manages the open/close state of the modal and
 * the SVG code selected.
 *
 * @param {Object}   props               - Component props.
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Function to update block attributes.
 *
 * @return {JSX.Element} The rendered IconPicker component.
 */

function IconPicker({
  attributes,
  setAttributes
}) {
  const [isOpen, setOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [svgCode, setSvgCode] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_toolbar_button__WEBPACK_IMPORTED_MODULE_2__["default"], {
      attributes: attributes,
      setAttributes: setAttributes,
      setOpen: setOpen,
      setSvgCode: setSvgCode
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_modal__WEBPACK_IMPORTED_MODULE_1__["default"], {
      attributes: attributes,
      setAttributes: setAttributes,
      isOpen: isOpen,
      setOpen: setOpen,
      setSvgCode: setSvgCode,
      svgCode: svgCode
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (IconPicker);

/***/ }),

/***/ "./src/components/icon-picker/modal.js":
/*!*********************************************!*\
  !*** ./src/components/icon-picker/modal.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * WordPress dependencies
 */




/**
 * IconPickerModal Component
 *
 * A modal for inputting and previewing custom SVG icons.
 * Validates the SVG code and allows users to insert it into the block attributes.
 *
 * @param {Object}   props               - Component props.
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Function to update block attributes.
 * @param {boolean}  props.isOpen        - Flag to control modal visibility.
 * @param {Function} props.setOpen       - Function to toggle modal open state.
 * @param {string}   props.svgCode       - Current SVG code input.
 * @param {Function} props.setSvgCode    - Function to update the SVG code.
 *
 * @return {JSX.Element|null} Modal component for inserting a custom SVG icon, or null if modal is closed.
 */

function IconPickerModal({
  attributes,
  setAttributes,
  isOpen,
  setOpen,
  svgCode,
  setSvgCode
}) {
  const [isSvgValid, setIsSvgValid] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [validationError, setValidationError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const closeModal = () => setOpen(false);

  /**
   * Validate the SVG code.
   *
   * @param {string} svg The SVG code to validate.
   * @return {boolean} Whether the SVG is valid.
   */
  const validateSvg = svg => {
    if (!svg) {
      setValidationError('');
      return false;
    }
    const trimmed = svg.trim();
    if (!trimmed) {
      return false;
    }

    // Match all <svg>...</svg> blocks
    const matches = trimmed.match(/<svg[\s\S]*?>[\s\S]*?<\/svg>/gi);
    if (!matches || matches.length === 0) {
      setValidationError((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Invalid SVG code. Please provide valid SVG(s).', 'blablablocks-tabs-block'));
      return false;
    }

    // Join matched SVGs and compare with the trimmed input
    const joinedSVGs = matches.join('').trim();
    if (joinedSVGs !== trimmed) {
      setValidationError((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Only SVG code is allowed. Remove any extra text outside <svg> tags.', 'blablablocks-tabs-block'));
      return false;
    }

    // Clear any previous error
    setValidationError('');
    return true;
  };

  /**
   * Handle SVG code changes.
   * Updates the SVG code state and validates the new code.
   *
   * @param {string} value The new SVG code.
   */
  const handleSvgCodeChange = value => {
    setSvgCode(value);
    setIsSvgValid(validateSvg(value));
  };

  /**
   * Handle inserting the SVG.
   */
  const handleInsertSvg = () => {
    if (isSvgValid) {
      setAttributes({
        tabIcon: svgCode
      });
      closeModal();
    }
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (isOpen) {
      const initialCode = svgCode || attributes.tabIcon || '';
      setIsSvgValid(validateSvg(initialCode));
    }
  }, [isOpen]);
  if (!isOpen) {
    return null;
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Modal, {
    className: "bbb-custom-icon-modal",
    title: attributes.tabIcon ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Edit Custom Icon', 'blablablocks-tabs-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Add Custom Icon', 'blablablocks-tabs-block'),
    size: 'large',
    onRequestClose: closeModal,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalGrid, {
      align: "stretch",
      templateColumns: 'auto 300px',
      gap: 5,
      style: {
        height: '100%'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextareaControl, {
        __nextHasNoMarginBottom: true,
        className: "bbb-icon-textarea",
        hideLabelFromVision: true,
        placeholder: "Paste your svg code here",
        value: svgCode || attributes.tabIcon,
        onChange: handleSvgCodeChange
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalVStack, {
        spacing: 5,
        justify: "space-between",
        style: {
          height: '100%'
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalVStack, {
          spacing: 5,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Card, {
            style: {
              height: '200px'
            },
            isRounded: false,
            children: isSvgValid ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
              className: "bbb-icon-preview",
              dangerouslySetInnerHTML: {
                __html: svgCode || attributes.tabIcon
              }
            }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
              className: "bbb-icon-preview",
              children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('SVG Preview', 'blablablocks-tabs-block')
            })
          }), validationError && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Notice, {
            status: "error",
            isDismissible: false,
            children: validationError
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalHStack, {
          justify: "flex-end",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
            variant: 'primary',
            onClick: handleInsertSvg,
            disabled: !isSvgValid,
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Insert custom icon', 'blablablocks-tabs-block')
          })
        })]
      })]
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (IconPickerModal);

/***/ }),

/***/ "./src/components/icon-picker/toolbar-button.js":
/*!******************************************************!*\
  !*** ./src/components/icon-picker/toolbar-button.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/code.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/library/reset.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);
/**
 * WordPress dependencies
 */




/**
 * IconPickerToolbarButton Component
 *
 * A toolbar button that provides a dropdown menu for managing custom SVG icons.
 * Users can open the icon picker modal or reset the selected SVG icon.
 *
 * @param {Object}   props               - Component props.
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Function to update block attributes.
 * @param {Function} props.setOpen       - Function to toggle modal open state.
 * @param {Function} props.setSvgCode    - Function to update the SVG code.
 *
 * @return {JSX.Element} A toolbar group containing icon-related actions.
 */

function IconPickerToolbarButton({
  attributes,
  setAttributes,
  setOpen,
  setSvgCode
}) {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToolbarGroup, {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToolbarDropdownMenu, {
      controls: [{
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__["default"],
        title: attributes.tabIcon ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Edit custom SVG icon', 'blablablocks-tabs-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Add custom SVG icon', 'blablablocks-tabs-block'),
        onClick: () => setOpen(true)
      }, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["default"],
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Reset icon', 'blablablocks-tabs-block'),
        onClick: () => {
          setAttributes({
            tabIcon: ''
          });
          setSvgCode('');
        },
        isDisabled: !attributes.tabIcon
      }],
      text: attributes.tabIcon ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Replace icon', 'blablablocks-tabs-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Add Icon', 'blablablocks-tabs-block'),
      icon: ''
    })
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (IconPickerToolbarButton);

/***/ }),

/***/ "./src/components/icons/tab-logo.js":
/*!******************************************!*\
  !*** ./src/components/icons/tab-logo.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Wordpress dependencies.
 */


/**
 * Tab logo icon
 */

const TabLogo = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SVG, {
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Path, {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M5.5498 10.3501V6.3501H9.8498V10.3501H11.3498V6.1001C11.3498 5.40974 10.7902 4.8501 10.0998 4.8501H5.2998C4.60945 4.8501 4.0498 5.40974 4.0498 6.1001V10.3501H5.5498ZM20 12.6001H4V14.1001L20 14.1001V12.6001ZM14 17.1001H4V18.6001H14V17.1001Z"
  })
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TabLogo);

/***/ }),

/***/ "./src/components/icons/tabs-logo.js":
/*!*******************************************!*\
  !*** ./src/components/icons/tabs-logo.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Wordpress dependencies.
 */


/**
 * Tabs logo icon
 */

const TabsLogo = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SVG, {
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Path, {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M5.2998 4.8501C4.60945 4.8501 4.0498 5.40974 4.0498 6.1001V10.3501H11.3498V6.1001C11.3498 5.40974 10.7902 4.8501 10.0998 4.8501H5.2998ZM14.2002 10.3501V7.1001H18.5002V10.3501H20.0002V6.8501C20.0002 6.15974 19.4406 5.6001 18.7502 5.6001H13.9502C13.2598 5.6001 12.7002 6.15974 12.7002 6.8501V10.3501H14.2002ZM20 12.6001H4V14.1001H20V12.6001ZM14 17.1001H4V18.6001H14V17.1001Z"
  })
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TabsLogo);

/***/ }),

/***/ "./src/components/icons/tabs-vertical-logo.js":
/*!****************************************************!*\
  !*** ./src/components/icons/tabs-vertical-logo.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Wordpress dependencies.
 */


/**
 * Tabs Vertical logo icon
 */

const TabsVerticalLogo = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.SVG, {
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Path, {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M4.8501 5.2998C4.8501 4.60945 5.40974 4.0498 6.1001 4.0498H10.3501V11.3498H6.1001C5.40974 11.3498 4.8501 10.7902 4.8501 10.0998V5.2998ZM10.3501 14.2002H7.1001V18.5002H10.3501V20.0002H6.8501C6.15974 20.0002 5.6001 19.4406 5.6001 18.7502V13.9502C5.6001 13.2598 6.15974 12.7002 6.8501 12.7002H10.3501V14.2002ZM16.1001 \n4.1001H22V5.6001H12.1001V4.1001ZM16.1001 7.1001H20V8.6001H12.1001V7.1001ZM16.1001 10.1001H18V11.6001H12.1001V10.1001Z"
  })
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TabsVerticalLogo);

/***/ }),

/***/ "./src/components/index.js":
/*!*********************************!*\
  !*** ./src/components/index.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ColorControlDropdown: () => (/* reexport safe */ _color_control__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   PatternList: () => (/* reexport safe */ _pattern_picker_pattern_list__WEBPACK_IMPORTED_MODULE_5__["default"]),
/* harmony export */   PatternSidebar: () => (/* reexport safe */ _pattern_picker_pattern_sidebar__WEBPACK_IMPORTED_MODULE_4__["default"]),
/* harmony export */   TabLogo: () => (/* reexport safe */ _icons_tab_logo__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   TabsLogo: () => (/* reexport safe */ _icons_tabs_logo__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   TabsVerticalLogo: () => (/* reexport safe */ _icons_tabs_vertical_logo__WEBPACK_IMPORTED_MODULE_2__["default"])
/* harmony export */ });
/* harmony import */ var _icons_tabs_logo__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./icons/tabs-logo */ "./src/components/icons/tabs-logo.js");
/* harmony import */ var _icons_tab_logo__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./icons/tab-logo */ "./src/components/icons/tab-logo.js");
/* harmony import */ var _icons_tabs_vertical_logo__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./icons/tabs-vertical-logo */ "./src/components/icons/tabs-vertical-logo.js");
/* harmony import */ var _color_control__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./color-control */ "./src/components/color-control.js");
/* harmony import */ var _pattern_picker_pattern_sidebar__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./pattern-picker/pattern-sidebar */ "./src/components/pattern-picker/pattern-sidebar.js");
/* harmony import */ var _pattern_picker_pattern_list__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./pattern-picker/pattern-list */ "./src/components/pattern-picker/pattern-list.js");
/**
 * Export Components.
 */







/***/ }),

/***/ "./src/components/pattern-picker/pattern-list.js":
/*!*******************************************************!*\
  !*** ./src/components/pattern-picker/pattern-list.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__);
/**
 * WordPress dependencies
 */






// Constants

const PATTERNS_PER_PAGE = 20;
const LOADING_DELAY = 300; // ms

/**
 * Component for displaying a list of block patterns with search and selection functionality.
 *
 * @param {Object}   props                  - The component props.
 * @param {string}   props.selectedCategory - The currently selected category for filtering patterns.
 * @param {string}   props.searchTerm       - The current search term for filtering patterns by title.
 * @param {Function} props.onSelect         - Callback function triggered when a pattern is selected.
 *
 * @return {JSX.Element} The rendered PatternList component.
 */
const PatternList = ({
  selectedCategory,
  searchTerm,
  onSelect
}) => {
  const [currentPage, setCurrentPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(1);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(true);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);

  // Get patterns from store with error handling
  const {
    patterns,
    hasResolved
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.useSelect)(select => {
    try {
      const coreSelect = select('core');
      return {
        patterns: coreSelect.getBlockPatterns(),
        hasResolved: coreSelect.hasFinishedResolution('getBlockPatterns')
      };
    } catch (err) {
      return {
        patterns: [],
        hasResolved: true,
        error: err
      };
    }
  }, [selectedCategory]);

  // Reset to page 1 when search or category changes
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    setCurrentPage(1);
  }, [searchTerm, selectedCategory]);

  // Handle loading state
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    setIsLoading(true);
    if (!hasResolved) {
      return;
    }
    const timeout = setTimeout(() => setIsLoading(false), LOADING_DELAY);
    return () => clearTimeout(timeout);
  }, [patterns, searchTerm, hasResolved]);

  // Filter patterns based on category and search term
  const filteredPatterns = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => {
    if (!patterns || !Array.isArray(patterns)) {
      return [];
    }
    return patterns.filter(pattern => {
      if (!pattern) {
        return false;
      }
      const matchesCategory = !selectedCategory || pattern.categories && pattern.categories.includes(selectedCategory);
      const matchesSearch = pattern.title && pattern.title.toLowerCase().includes((searchTerm || '').toLowerCase());
      return matchesCategory && matchesSearch;
    });
  }, [patterns, selectedCategory, searchTerm]);

  // Calculate pagination values
  const totalPages = Math.max(1, Math.ceil((filteredPatterns?.length || 0) / PATTERNS_PER_PAGE));

  // Ensure current page is valid after filtering changes
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [totalPages, currentPage]);

  // Get paginated patterns
  const paginatedPatterns = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => {
    return filteredPatterns.slice((currentPage - 1) * PATTERNS_PER_PAGE, currentPage * PATTERNS_PER_PAGE);
  }, [filteredPatterns, currentPage]);

  // Handle pattern selection with validation
  const handlePatternSelect = pattern => {
    if (pattern && typeof onSelect === 'function') {
      try {
        onSelect(pattern);
      } catch (err) {
        setError((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Failed to select pattern. Please try again.', 'blablablocks-tabs-block'));
      }
    }
  };

  // Handle pagination
  const goToPage = direction => {
    setCurrentPage(prev => {
      const newPage = prev + direction;
      return Math.max(1, Math.min(newPage, totalPages));
    });
  };

  // Render error message if needed
  if (error) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Notice, {
      status: "error",
      isDismissible: false,
      children: error
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
    className: "bbb-tabs-patterns-grid",
    children: isLoading ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
      className: "bbb-tabs-patterns-loading",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Spinner, {})
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
      children: [searchTerm && (() => {
        const count = filteredPatterns.length;
        /* translators: %d: Number of patterns found. */
        const label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %d: Number of patterns found. */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__._n)('%d pattern found', '%d patterns found', count, 'blablablocks-tabs-block'), count);
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalHeading, {
          className: "tabs-patterns-no-results",
          style: {
            paddingBottom: '32px'
          },
          children: label
        });
      })(), filteredPatterns?.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalGrid, {
        gap: 8,
        columns: [1, 2, 3],
        align: "start",
        className: "bbb-tabs-patterns-grid-content",
        children: paginatedPatterns.map(pattern => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
          className: "bbb-tabs-patterns-item",
          onClick: () => handlePatternSelect(pattern),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalVStack, {
            alignment: "top",
            align: "left",
            style: {
              width: '100%',
              height: '100%'
            },
            children: [pattern.content ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.BlockPreview, {
              blocks: wp.blocks.parse(pattern.content),
              viewportWidth: 800
            }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("div", {
              className: "bbb-tabs-patterns-preview-error",
              children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Preview not available', 'blablablocks-tabs-block')
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.__experimentalText, {
              align: "left",
              size: 12,
              children: pattern.title || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Untitled pattern', 'blablablocks-tabs-block')
            })]
          })
        }, pattern.name || `pattern-${pattern.id || Math.random()}`))
      }), filteredPatterns.length > 0 && totalPages > 1 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
        className: "bbb-tabs-patterns-pagination",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
          disabled: currentPage === 1,
          onClick: () => goToPage(-1),
          className: "tabs-patterns-pagination-prev",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Previous', 'blablablocks-tabs-block')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("span", {
          className: "tabs-patterns-pagination-status",
          children: `${currentPage} / ${totalPages}`
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
          disabled: currentPage === totalPages,
          onClick: () => goToPage(1),
          className: "tabs-patterns-pagination-next",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Next', 'blablablocks-tabs-block')
        })]
      })]
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PatternList);

/***/ }),

/***/ "./src/components/pattern-picker/pattern-sidebar.js":
/*!**********************************************************!*\
  !*** ./src/components/pattern-picker/pattern-sidebar.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);
/**
 * WordPress dependencies
 */





/**
 * PatternSidebar component renders a sidebar for selecting block pattern categories
 *
 * @param {Object}      props                     - The component props.
 * @param {string|null} props.selectedCategory    - The currently selected pattern category.
 * @param {Function}    props.setSelectedCategory - Function to update the selected pattern category.
 * @param {string}      props.searchTerm          - The current search term for filtering patterns by title.
 * @param {Function}    props.setSearchTerm       - Function to update the search term for filtering patterns.
 *
 * @return {JSX.Element} The rendered sidebar component.
 */

const PatternSidebar = ({
  selectedCategory,
  setSelectedCategory,
  searchTerm,
  setSearchTerm
}) => {
  // Fetch pattern categories and block patterns with error handling.
  const {
    patternCategories,
    blockPatterns,
    error
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useSelect)(select => {
    try {
      const core = select('core');
      return {
        patternCategories: core.getBlockPatternCategories() || [],
        blockPatterns: core.getBlockPatterns() || [],
        error: null
      };
    } catch (err) {
      return {
        patternCategories: [],
        blockPatterns: [],
        error: err
      };
    }
  }, []);

  // Memoize the filtered categories to optimize performance.
  const filteredCategories = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useMemo)(() => {
    return patternCategories.filter(category => blockPatterns.some(pattern => Array.isArray(pattern.categories) && pattern.categories.includes(category.name)));
  }, [patternCategories, blockPatterns]);

  // Show an error message if data fetching fails.
  if (error) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "bbb-tabs-patterns-sidebar--error",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Failed to load block patterns.', 'blablablocks-tabs-block')
    });
  }

  // Simple loading state when no data is available.
  if (!patternCategories.length && !blockPatterns.length) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "bbb-tabs-patterns-sidebar--loading",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading…', 'blablablocks-tabs-block')
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    className: "bbb-tabs-patterns-sidebar",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SearchControl, {
      __nextHasNoMarginBottom: true,
      value: searchTerm,
      placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Search', 'blablablocks-tabs-block'),
      onChange: setSearchTerm
    }), !searchTerm && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
      className: "bbb-tabs-patterns-sidebar__list",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
        __next40pxDefaultSize: true,
        isPressed: selectedCategory === null,
        onClick: () => setSelectedCategory(null),
        style: {
          display: 'flex',
          justifyContent: 'space-between',
          width: '100%',
          textAlign: 'left'
        },
        children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('All', 'blablablocks-tabs-block'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("span", {
          className: "bbb-tabs-patterns-sidebar__count",
          children: blockPatterns.length
        })]
      }), filteredCategories.map(({
        name,
        label
      }) => {
        const count = blockPatterns.filter(pattern => Array.isArray(pattern.categories) && pattern.categories.includes(name)).length || 0;
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
          __next40pxDefaultSize: true,
          isPressed: selectedCategory === name,
          onClick: () => setSelectedCategory(name),
          style: {
            display: 'flex',
            justifyContent: 'space-between',
            width: '100%',
            textAlign: 'left'
          },
          children: [label, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("span", {
            className: "bbb-tabs-patterns-sidebar__count",
            children: count
          })]
        }, name);
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PatternSidebar);

/***/ }),

/***/ "./src/tab/edit.js":
/*!*************************!*\
  !*** ./src/tab/edit.js ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! clsx */ "./node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_slotFill__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/slotFill */ "./src/utils/slotFill.js");
/* harmony import */ var _placeholder__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./placeholder */ "./src/tab/placeholder.js");
/* harmony import */ var _components_icon_picker__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/icon-picker */ "./src/components/icon-picker/index.js");
/* harmony import */ var _utils_style__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/style */ "./src/utils/style.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__);
/**
 * WordPress dependencies
 */







/**
 * Internal dependencies
 */





/**
 * The Edit component for the Tab block.
 *
 * @param {Object}   props               - Component props.
 * @param {string}   props.clientId      - The client ID for this block instance.
 * @param {boolean}  props.isSelected    - Whether the block is currently selected.
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Function to update block attributes.
 * @return {JSX.Element} The component rendering for the block editor.
 */

function Edit({
  clientId,
  isSelected,
  attributes,
  setAttributes
}) {
  const {
    updateBlockAttributes,
    selectBlock
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useDispatch)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.store);

  /**
   * Retrieve block-related data using the `useSelect` hook.
   */
  const {
    hasChildBlocks,
    tabsClientId,
    hasTabSelected,
    isDefaultTab,
    blockIndex,
    isTabsClientSelected,
    forceDisplay,
    hasInnerBlocksSelected,
    lastSelectedTabClientId,
    parentAttrs
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useSelect)(select => {
    const {
      getBlockOrder,
      getBlockIndex,
      getBlockRootClientId,
      getBlockAttributes,
      hasSelectedInnerBlock,
      isBlockSelected,
      getMultiSelectedBlocksEndClientId
    } = select('core/block-editor');
    const rootClientId = getBlockRootClientId(clientId);
    const parentBlockAttrs = getBlockAttributes(rootClientId);
    const innerHasTabSelected = hasSelectedInnerBlock(rootClientId, true);
    const innerHasInnerBlocksSelected = hasSelectedInnerBlock(clientId, true);
    const innerBlockIndex = getBlockIndex(clientId);
    const totalTabsCount = getBlockOrder(rootClientId).length;

    // Check if activeTab is a valid index and if this tab is the active one
    const activeTab = parentBlockAttrs?.activeTab;
    const isValidActiveTab = typeof activeTab === 'number' && activeTab >= 0 && activeTab < totalTabsCount;
    const innerIsDefaultTab = isValidActiveTab ? activeTab === innerBlockIndex : innerBlockIndex === 0;
    const innerIsTabsClientSelected = isBlockSelected(rootClientId);
    return {
      blockIndex: innerBlockIndex,
      tabsClientId: rootClientId,
      hasChildBlocks: getBlockOrder(clientId).length > 0,
      hasInnerBlocksSelected: innerHasInnerBlocksSelected,
      isTabsClientSelected: innerIsTabsClientSelected,
      isDefaultTab: innerIsDefaultTab,
      forceDisplay: innerIsDefaultTab && innerIsTabsClientSelected,
      hasTabSelected: innerHasTabSelected,
      lastSelectedTabClientId: getMultiSelectedBlocksEndClientId(),
      parentAttrs: parentBlockAttrs
    };
  }, [clientId]);

  /**
   * Determines if the current tab should be displayed as selected based on
   * various selection states and conditions.
   *
   * @type {boolean}
   */
  const isTabSelected = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useMemo)(() => {
    if (isSelected || hasInnerBlocksSelected || forceDisplay) {
      return true;
    }
    if (isDefaultTab && !isTabsClientSelected && !isSelected && !hasTabSelected) {
      return true;
    }

    // If multiple tabs are selected, only show the last one
    if (hasTabSelected && lastSelectedTabClientId === clientId) {
      return true;
    }
    return false;
  }, [clientId, isSelected, hasInnerBlocksSelected, isDefaultTab, forceDisplay, isTabsClientSelected, hasTabSelected, lastSelectedTabClientId]);
  const typographyProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.getTypographyClassesAndStyles)(parentAttrs);

  /**
   * Props for the block container.
   * @type {Object}
   */
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.useBlockProps)({
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__["default"])('blablablocks-tab', 'blablablocks-tab-container', 'blablablocks-tabs__' + parentAttrs.orientation, 'blablablocks-tabs__' + parentAttrs.verticalPosition, 'blablablocks-tabs-icon__' + parentAttrs.iconPosition)
  });

  /**
   * Props for the inner blocks container.
   * @type {Object}
   */
  const innerBlocksProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.useInnerBlocksProps)({
    className: 'blablablocks-tab-content',
    'aria-labelledby': `tab-${attributes.tabId}`,
    id: `tab-${attributes.tabId}`,
    role: 'tabpanel',
    tabIndex: isTabSelected ? 0 : -1
  });

  /**
   * Sets the default tab by updating the `activeTab` attribute of the parent Tabs block.
   *
   * @param {boolean} value - The value to set for the active tab.
   */
  const handleSetDefault = value => {
    updateBlockAttributes(tabsClientId, {
      activeTab: value ? blockIndex : 0
    });
  };

  /**
   * Set the `tabId` attribute.
   *
   * This effect ensures each tab has a unique identifier by setting the tabId
   * attribute to the clientId. This also handles duplication cases where the
   * tabId might have been copied from another block.
   */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (attributes.tabId !== clientId) {
      setAttributes({
        tabId: clientId
      });
    }
  }, [clientId, attributes.tabId]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
      ...blockProps,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_utils_slotFill__WEBPACK_IMPORTED_MODULE_6__.TabFill, {
        tabsClientId: tabsClientId,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)("div", {
          id: attributes.tabId,
          className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__["default"])('blablablock-tab-btn', {
            'is-bbb-active-tab': isTabSelected
          }),
          role: "tab",
          tabIndex: 0,
          "aria-selected": isTabSelected,
          "aria-controls": attributes.tabId,
          onClick: () => selectBlock(clientId),
          onKeyDown: e => {
            if (e.key === 'Enter' || e.key === ' ') {
              // Don't handle if clicking on or inside the RichText component
              if (e.target.closest('.tab-button-text')) {
                return;
              }
              e.preventDefault();
              selectBlock(clientId);
            }
          },
          ...(0,_utils_style__WEBPACK_IMPORTED_MODULE_9__.getTabButtonStyles)(parentAttrs, isTabSelected),
          children: [attributes.tabIcon && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("span", {
            className: "bbb-tab-icon",
            dangerouslySetInnerHTML: {
              __html: attributes.tabIcon
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.RichText, {
            tagName: "span",
            className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__["default"])('tab-button-text', typographyProps.className),
            withoutInteractiveFormatting: true,
            value: attributes.tabname,
            placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Add Tab Label…'),
            onChange: value => setAttributes({
              tabname: value
            }),
            style: typographyProps.style
          })]
        })
      }), isTabSelected && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.Fragment, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_utils_slotFill__WEBPACK_IMPORTED_MODULE_6__.TabsListSlot, {
          tabsClientId: tabsClientId,
          attributes: parentAttrs
        }, blockIndex), hasChildBlocks ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          ...innerBlocksProps
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_placeholder__WEBPACK_IMPORTED_MODULE_7__["default"], {
          clientId: clientId
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.InspectorControls, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Settings', 'blablablocks-tabs-block'),
        initialOpen: true,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Set as default tab', 'blablablocks-tabs-block'),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('This tab will be active when the page first loads.', 'blablablocks-tabs-block'),
          checked: isDefaultTab,
          onChange: value => handleSetDefault(value)
        })
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.BlockControls, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_components_icon_picker__WEBPACK_IMPORTED_MODULE_8__["default"], {
        attributes: attributes,
        setAttributes: setAttributes
      })
    })]
  });
}

/***/ }),

/***/ "./src/tab/placeholder.js":
/*!********************************!*\
  !*** ./src/tab/placeholder.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components */ "./src/components/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);
/**
 * WordPress dependencies
 */







/**
 * Internal dependencies
 */


/**
 * This component serves as a placeholder for the Tab block, displaying a block variation picker.
 * It allows users to choose from predefined variations for initializing the block with default settings.
 *
 * @param {Object} props          Component props.
 * @param {string} props.clientId The client ID for this block instance.
 * @return {JSX.Element} The placeholder component for the Tabs block.
 */

function Placeholder({
  clientId
}) {
  const {
    replaceInnerBlocks
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useDispatch)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_5__.store);
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_5__.useBlockProps)({
    className: 'bbb-tab-placeholder'
  });
  const [step, setStep] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [isModalOpen, setIsModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [selectedCategory, setSelectedCategory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [searchTerm, setSearchTerm] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  /**
   * Creates a blank tab with default content
   */
  const handleSkip = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(() => {
    try {
      const defaultTemplate = [['core/paragraph']];
      const blocks = (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__.createBlocksFromInnerBlocksTemplate)(defaultTemplate);
      replaceInnerBlocks(clientId, blocks, true);
      setStep('blank');
    } catch (err) {
      setError((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Failed to create blank tab. Please try again.', 'blablablocks-tabs-block'));
    }
  }, [clientId, replaceInnerBlocks]);

  /**
   * Applies a selected pattern to the tab content
   *
   * @param {Object} pattern - The pattern object containing content to apply
   */
  const applyPattern = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(pattern => {
    if (!pattern || !pattern.content) {
      setError((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Invalid pattern selected. Please choose another pattern.', 'blablablocks-tabs-block'));
      return;
    }
    try {
      const parsedBlocks = (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__.parse)(pattern.content);
      if (!parsedBlocks || parsedBlocks.length === 0) {
        throw new Error('No valid blocks found in pattern');
      }
      replaceInnerBlocks(clientId, parsedBlocks, true);
      setIsModalOpen(false);
      setStep('pattern');
    } catch (err) {
      setError((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Failed to apply pattern. Please try another one.', 'blablablocks-tabs-block'));
    }
  }, [clientId, replaceInnerBlocks]);

  /**
   * Clears current error message
   */
  const dismissError = () => {
    setError(null);
  };

  /**
   * Opens pattern selection modal
   */
  const openPatternModal = () => {
    setIsModalOpen(true);
    setError(null);
  };

  /**
   * Closes pattern selection modal
   */
  const closePatternModal = () => {
    setIsModalOpen(false);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
    ...blockProps,
    children: [error && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Notice, {
      status: "error",
      isDismissible: true,
      onRemove: dismissError,
      children: error
    }), !step && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Placeholder, {
      icon: _components__WEBPACK_IMPORTED_MODULE_6__.TabLogo,
      instructions: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Choose a pattern or start blank.', 'blablablocks-tabs-block'),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Tab', 'blablablocks-tabs-block'),
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
        variant: "primary",
        onClick: openPatternModal,
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Choose', 'blablablocks-tabs-block')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
        variant: "secondary",
        onClick: handleSkip,
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Start blank', 'blablablocks-tabs-block')
      })]
    }), isModalOpen && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Modal, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Patterns', 'blablablocks-tabs-block'),
      isFullScreen: true,
      onRequestClose: closePatternModal,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
        className: "bbb-tabs-patterns-container",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components__WEBPACK_IMPORTED_MODULE_6__.PatternSidebar, {
          selectedCategory: selectedCategory,
          setSelectedCategory: setSelectedCategory,
          setSearchTerm: setSearchTerm,
          searchTerm: searchTerm
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_components__WEBPACK_IMPORTED_MODULE_6__.PatternList, {
          selectedCategory: selectedCategory,
          searchTerm: searchTerm,
          onSelect: applyPattern,
          onError: setError
        })]
      })
    })]
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Placeholder);

/***/ }),

/***/ "./src/tab/save.js":
/*!*************************!*\
  !*** ./src/tab/save.js ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Wordpress dependencies
 */


/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @return {JSX.Element}	The block's save component.
 */

function save() {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.InnerBlocks.Content, {});
}

/***/ }),

/***/ "./src/utils/slotFill.js":
/*!*******************************!*\
  !*** ./src/utils/slotFill.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TabFill: () => (/* binding */ TabFill),
/* harmony export */   TabsListSlot: () => (/* binding */ TabsListSlot)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! clsx */ "./node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _style__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./style */ "./src/utils/style.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);
/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */


/**
 * Create a unique SlotFill pair using a Symbol to avoid name collisions.
 */

const {
  Fill,
  Slot
} = (0,_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.createSlotFill)(Symbol('BlaBlaBlocksTabsList'));

/**
 * TabFill Component
 *
 * This component registers a Fill for the BlaBlaBlocksTabsList Slot.
 *
 * @param {Object}          props
 * @param {React.ReactNode} props.children     - Elements to be rendered inside the Fill.
 * @param {string}          props.tabsClientId - Unique identifier used to scope the Fill to a specific Tabs instance.
 * @return {JSX.Element} A Fill component scoped to the specified Tabs instance.
 */
const TabFill = ({
  children,
  tabsClientId
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(Fill, {
    name: `BlaBlaBlocksTabsList-${tabsClientId}`,
    children: children
  });
};

/**
 * BlaBlaBlocksTabsListSlot Component
 *
 * This component renders the Slot for a specific Tabs instance.
 * Any TabFill with a matching name will render inside this Slot.
 *
 * @param {Object} props
 * @param {string} props.tabsClientId - Unique identifier used to scope the Slot to a specific Tabs instance.
 * @param {Object} props.attributes   - Block attributes used to derive styling props.
 * @return {JSX.Element} A Slot component that renders TabFill components matching the specified Tabs instance.
 */
const TabsListSlot = ({
  tabsClientId,
  attributes
}) => {
  const {
    className,
    style
  } = (0,_style__WEBPACK_IMPORTED_MODULE_3__.getTabsContainerProps)(attributes);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(Slot, {
    name: `BlaBlaBlocksTabsList-${tabsClientId}`,
    bubblesVirtually: true,
    as: "div",
    role: "tablist",
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__["default"])(className, 'blablablocks-tabs-buttons'),
    style: style
  });
};

/***/ }),

/***/ "./src/utils/style.js":
/*!****************************!*\
  !*** ./src/utils/style.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   generateStyles: () => (/* binding */ generateStyles),
/* harmony export */   getTabButtonStyles: () => (/* binding */ getTabButtonStyles),
/* harmony export */   getTabsContainerProps: () => (/* binding */ getTabsContainerProps)
/* harmony export */ });
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__);
/**
 * WordPress dependencies
 */

const DEFAULT_GAP = '0.5em';

/**
 * Generates CSS gap styles for a block based on the provided gap and orientation.
 *
 * @param {string|object} blockGap                   - The gap value for the block. Can be a string (e.g., "10px") or an object with `top` and `left` properties.
 * @param {string}        [orientation='horizontal'] - The orientation of the block. Can be 'horizontal' or 'vertical'.
 * @return {Array<string>} - An array containing two CSS gap values
 */
const generateGapStyles = (blockGap, orientation = 'horizontal') => {
  let tabListGap = DEFAULT_GAP;
  let tabGap = DEFAULT_GAP;
  if (typeof blockGap === 'string') {
    tabListGap = blockGap;
    tabGap = blockGap;
  } else if (typeof blockGap === 'object' && blockGap !== null) {
    tabListGap = blockGap.top || DEFAULT_GAP;
    tabGap = blockGap.left || DEFAULT_GAP;
  }

  // Convert to valid CSS values
  const main = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.__experimentalGetGapCSSValue)(tabListGap);
  const cross = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.__experimentalGetGapCSSValue)(tabGap);

  // If vertical, swap main⇄cross
  return orientation === 'vertical' ? [main, cross] : [cross, main];
};

/**
 * Resolves a spacing size value into a usable CSS value.
 *
 * @param {string|number} value        - The input spacing size value.
 * @param {string|number} defaultValue - The default value.
 * @return {string} - A valid CSS spacing size value.
 */
const resolveSpacingSizeValue = (value, defaultValue = '0px') => {
  if (typeof value === 'string') {
    if (value.startsWith('var:')) {
      // Convert "var:some|value" into "var(--wp--some--value)"
      return `var(${value.replace('var:', '--wp--').replace(/\|/g, '--')})`;
    }
    return value; // If it's a valid CSS string, return as-is
  }
  return typeof value === 'number' ? `${value}px` : defaultValue;
};

/**
 * Helper to get border props with numeric radius handling.
 *
 * @param {Object}   borderAttributes - Attributes object to pass to useBorderProps
 * @param {Function} radiusPath       - Function that returns the border radius value
 * @return {Object} Border props with className and style, including converted numeric radius
 */
const getBorderPropsWithRadius = (borderAttributes, radiusPath) => {
  const rawBorder = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.__experimentalUseBorderProps)(borderAttributes);
  const borderRadius = radiusPath?.();
  return {
    ...rawBorder,
    style: {
      ...rawBorder.style,
      ...(typeof borderRadius === 'number' && {
        borderRadius: `${borderRadius}px`
      })
    }
  };
};

/**
 * Helper function to resolve color value based on slug
 *
 * @param {Object} colorObj - Color object with color and slug properties
 * @param {string} fallback - Fallback color value
 * @return {string} - CSS color value or custom property
 */
const resolveColorValue = (colorObj, fallback) => {
  if (!colorObj) {
    return fallback;
  }

  // If we have a slug, use the WordPress preset color custom property
  if (colorObj.slug) {
    return `var(--wp--preset--color--${colorObj.slug})`;
  }

  // Otherwise use the direct color value
  return colorObj.color || fallback;
};

/**
 * Generates a set of CSS variable mappings based on provided attributes.
 * The returned object excludes variables with invalid or undefined values.
 *
 * @param {Object} attributes - The attributes used to customize styles.
 * @return {Object} - An object with CSS variable definitions.
 */
const generateStyles = (attributes = {}) => {
  const styles = {};

  // Padding
  const padding = attributes.tabPadding || {};
  styles['--bbb-tab-padding'] = [resolveSpacingSizeValue(padding.top, '5px'), resolveSpacingSizeValue(padding.right, '15px'), resolveSpacingSizeValue(padding.bottom, '5px'), resolveSpacingSizeValue(padding.left, '15px')].join(' ');

  // Colors for different states
  const colorDefaults = {
    default: {
      text: '#000',
      bg: '#fff',
      icon: '#000'
    },
    hover: {
      text: '#fff',
      bg: '#000',
      icon: '#fff'
    },
    active: {
      text: '#fff',
      bg: '#000',
      icon: '#fff'
    }
  };
  Object.entries(colorDefaults).forEach(([state, defaults]) => {
    const stateKey = state === 'default' ? 'default' : state;

    // Text color
    styles[`--bbb-tab-text-${state}-color`] = resolveColorValue(attributes.tabTextColor?.[stateKey], defaults.text);

    // Background color
    styles[`--bbb-tab-background-${state}-color`] = resolveColorValue(attributes.tabBackgroundColor?.[stateKey], defaults.bg);

    // Icon color
    const iconColorValue = attributes.tabIconColor?.[stateKey] || (state === 'active' ? attributes.tabIconColor?.default : null);
    styles[`--bbb-tab-icon-${state}-color`] = resolveColorValue(iconColorValue, defaults.icon);
  });

  // Other styles
  styles['--bbb-tab-buttons-justify-content'] = attributes.justification || 'left';
  styles['--bbb-tab-icon-size'] = `${attributes.iconSize || 24}px`;

  // Gap styles
  const [listGap, tabsGap] = generateGapStyles(attributes.style?.spacing?.blockGap || null, attributes.orientation);
  styles['--bbb-tabs-list-gap'] = listGap;
  styles['--bbb-tabs-gap'] = tabsGap;
  return styles;
};

/**
 * Return consolidated className + style for the Tabs container:
 * – spacing classes & styles
 * – border props (with numeric radius)
 * – horizontal margin based on orientation/justification
 *
 * @param {Object} attributes - The attributes used to customize styles.
 * @return {{ className: string, style: Object }} An object containing the `className` string and inline `style` object.
 */
function getTabsContainerProps(attributes) {
  // spacing
  const spacingProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.__experimentalGetSpacingClassesAndStyles)(attributes);

  // color
  const colorProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.__experimentalGetColorClassesAndStyles)(attributes);

  // border (useBorderProps gives { className, style })
  const borderProps = getBorderPropsWithRadius(attributes, () => attributes.style?.border?.radius);

  // margin
  const marginStyle = attributes.orientation === 'horizontal' ? (() => {
    switch (attributes.justification) {
      case 'right':
        return {
          margin: '0 0 0 auto'
        };
      case 'center':
        return {
          margin: '0 auto'
        };
      case 'left':
      default:
        return {
          margin: '0 0 auto'
        };
    }
  })() : {};

  // width
  const width = attributes.orientation === 'vertical' ? {
    minWidth: `${attributes.width || 50}%`
  } : {};

  // combine
  return {
    className: [spacingProps.classes, borderProps.className, colorProps.className].filter(Boolean).join(' '),
    style: {
      ...spacingProps.style,
      ...borderProps.style,
      ...colorProps.style,
      ...marginStyle,
      ...width
    }
  };
}

/**
 * Return consolidated style for the Tab button:
 *
 * @param {Object}  attributes - The attributes used to customize styles.
 * @param {boolean} isActive   - Whether this tab is currently active.
 * @return {{ style: Object }}  An object containing the inline `style` for the tab button.
 */
function getTabButtonStyles(attributes, isActive) {
  // If tabBorder has an onActive flag and it's true, only apply border when this tab is active.
  const shouldApplyBorder = attributes?.tabBorder?.onActive ? isActive : true;

  // Tab Border (only if allowed by shouldApplyBorder)
  const borderInput = shouldApplyBorder ? {
    style: attributes?.tabBorder
  } : {
    style: {}
  };
  const borderProps = getBorderPropsWithRadius(borderInput, () => attributes?.tabBorder?.border?.radius);
  return {
    style: {
      ...borderProps.style
    }
  };
}

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "@wordpress/primitives":
/*!************************************!*\
  !*** external ["wp","primitives"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["primitives"];

/***/ }),

/***/ "./node_modules/clsx/dist/clsx.mjs":
/*!*****************************************!*\
  !*** ./node_modules/clsx/dist/clsx.mjs ***!
  \*****************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   clsx: () => (/* binding */ clsx),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

/***/ }),

/***/ "./src/tab/block.json":
/*!****************************!*\
  !*** ./src/tab/block.json ***!
  \****************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"blablablocks/tab","title":"Tab","description":"A single tab within a tabs block.","parent":["blablablocks/tabs"],"attributes":{"tabname":{"type":"string"},"tabId":{"type":"string"},"tabIcon":{"type":"string"}},"supports":{"html":false},"textdomain":"blablablocks-tab-block","editorScript":"file:./index.js","render":"file:./render.php"}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**************************!*\
  !*** ./src/tab/index.js ***!
  \**************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./edit */ "./src/tab/edit.js");
/* harmony import */ var _save__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./save */ "./src/tab/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src/tab/block.json");
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components */ "./src/components/index.js");
/**
 * Wordpress dependencies
 */


/**
 * Internal dependencies
 */





/**
 * Register a slide block
 */
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__.name, {
  icon: _components__WEBPACK_IMPORTED_MODULE_4__.TabLogo,
  edit: _edit__WEBPACK_IMPORTED_MODULE_1__["default"],
  save: _save__WEBPACK_IMPORTED_MODULE_2__["default"]
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map