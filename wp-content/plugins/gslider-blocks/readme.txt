=== Slider Block for Gutenberg Gutenslider by GSlider ===
Contributors: noruzzaman, sanaf
Tags: slider, carousel, logo carousel, slideshow, gutenberg
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.1.6
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Logo Carousel, Image Slider & Testimonial blocks for Gutenberg. 7+ effects, responsive design & full customization!

== Description ==

The **gslider Blocks** plugin allows you to easily add customizable and responsive sliders to your WordPress website. Perfect for both beginners and developers, this plugin provides powerful slider blocks for the WordPress block editor (Gutenberg). Customize slider content, transitions, navigation, and more, while enjoying full control over slider styling and functionality.

== Available Blocks ==

**1. Base Slider Block**
Create stunning hero sliders with predefined content elements. Perfect for homepage banners, promotional sliders, and featured content showcases.

* Pre-built slide structure with Title, Subtitle, Description, and Button
* Multiple transition effects (Slide, Fade, Cube, Coverflow, Flip, Cards, Creative)
* Full background image/color support with overlay options
* Responsive navigation arrows and pagination controls
* Content alignment and positioning controls
* Custom spacing and padding options

**2. Flexi Content Block**
Build completely custom slides using any Gutenberg blocks. Ultimate flexibility for creative slider designs.

* Use ANY WordPress blocks inside slides (images, text, buttons, galleries, etc.)
* Full creative control with InnerBlocks support
* Multiple slide effects including Fade, Slide, Cube, and more
* Responsive height options (Fixed, Auto, Minimum height)
* Loop, autoplay, and touch/swipe support
* Keyboard and mousewheel navigation

**3. Logo Carousel Block**
Display client logos, partner brands, or sponsor images in a beautiful, animated carousel.

* Easy logo management with drag & drop reordering
* Hover effects: Grayscale-to-color, opacity, scale, and lift animations
* Clickable logos with custom URLs and link settings
* Tooltips and captions with full styling options
* Responsive slides per view configuration
* Lazy loading for optimized performance

**4. Image Slider Block**
A versatile carousel for displaying images, brand assets, or partner logos with advanced interactive features.

* Lightbox Support: View images in a stunning full-screen overlay
* Custom Tooltips: Add descriptive hover text with complete styling control (typography, colors, position)
* Clickable Images: Link each image to custom internal or external URLs
* Caption Support: Display and style image captions for better context
* Responsive Design: Fully customizable columns and spacing for Desktop, Tablet, and Mobile
* Advanced Transitions: Slide, Fade, Cube, and other 3D effects powered by Swiper.js

== Key Features ==

* **Multiple Transition Effects:** Slide, Fade, Cube, Coverflow, Flip, Cards, and Creative effects
* **Fully Responsive:** Separate settings for Desktop, Tablet, and Mobile
* **Navigation Controls:** Customizable arrows (icons or images) and pagination (bullets, fraction, progressbar)
* **Performance Optimized:** Built with Swiper.js for smooth, hardware-accelerated animations
* **Accessibility Ready:** Keyboard navigation, ARIA labels, and touch support
* **Custom Styling:** Full control over colors, typography, spacing, borders, and shadows
* **Google Fonts:** Built-in typography options with Google Fonts integration

== Support ==

If you have any questions, please email us at **[gsliderblocks@gmail.com](mailto:gsliderblocks@gmail.com)**

== Installation ==

There are no prerequisites for installing **gslider Blocks**. You can follow the steps below to complete the installation.

= Automatic Installation =
1.  Go to `Plugins > Add New` screen from your WordPress website dashboard.
2.  Search for `gslider Blocks`.
3.  Install and activate the plugin, that's it!

== Frequently Asked Questions ==

= Does the plugin come for free? =

Yes, it's completely free.

= Can I customize the appearance of each slide individually? =

Yes, each slide can be customized independently with different content, backgrounds, and styling options.

= Does gslider work with any WordPress theme? =

Yes, gslider Blocks is designed to work with any WordPress theme that supports the block editor.

= What is the difference between Base Slider and Flexi Content? =

Base Slider has pre-built content elements (title, subtitle, description, button) for quick setup. Flexi Content allows you to use any Gutenberg blocks inside slides for complete creative freedom.

= Why do my slides show overlapping content with Creative, Cube, Cards, or Flip effects? =

These are 3D transition effects where slides are stacked in layers. If your slides have transparent backgrounds, content from slides behind will show through. To fix this, add a background color or background image to each slide in your slider settings.

== Screenshots ==

== Changelog ==

= 1.1.6 – February 8, 2026 = 
**New Block: Image Slider**
- Added Image Slider block with Swiper.js integration
- Lightbox Support: View images in a stunning full-screen overlay
- Custom Tooltips: Add descriptive hover text with complete styling control (typography, colors, position)
- Clickable Images: Link each image to custom internal or external URLs
- Caption Support: Display and style image captions for better context
- Responsive Design: Fully customizable columns and spacing for Desktop, Tablet, and Mobile
- Advanced Transitions: Slide, Fade, Cube, and other 3D effects powered by Swiper.js

= 1.1.5 – December 30, 2025 = 
**Fixed: Fade effect initial load glitch**
- Resolved an issue where the first slide would briefly animate from right to left when using the fade effect on page load
- Improved slider initialization to disable transitions during initial positioning
- Fix applies to both wrapper and individual slide elements for consistent behavior

= 1.1.4 – December 19, 2025 = 
**New: SVG Icons support for toolbar actions**
- Icons are now consistent across Logo Carousel and Image Slider blocks
- Improved code maintainability with single-source icon management

= 1.1.3 – December 16, 2025 = 
**New Block: Logo Carousel**
- Added fully-featured Logo Carousel block with Swiper.js integration
- Logo Management: Add, remove, duplicate, and reorder logos with drag & drop
- Advanced Carousel Controls: Autoplay, infinite loop, navigation arrows, pagination (bullets/fraction/progressbar)
- Responsive Settings: Configure slides per view, spacing, and speed for Desktop, Tablet, and Mobile
- Logo Styling: Customizable hover effects including grayscale-to-color transition, opacity, scale, and lift animations
- Logo Links: Enable clickable logos with custom URLs, target, and rel attributes
- Tooltips: Add custom tooltip text with full styling controls (typography, colors, positioning, spacing, borders)
- Captions: Display logo captions with complete styling options
- Responsive Visibility: Show/hide individual logos on specific devices
- Vertical Alignment: Control logo positioning (top, middle, bottom) within slides
- Performance: Built-in lazy loading support for optimized page speed
- Navigation: Customizable arrow icons/images with flexible positioning (inside/outside)
- Accessibility: Keyboard navigation, mousewheel control, and ARIA labels
- Google Fonts: Full support for custom typography with Google Fonts integration

= 1.1.2 – December 04, 2025 = 
- **Added**: keyboard Navigation control

= 1.1.1 – November 15, 2025 = 
- **Fixed**: Minor CSS issues

= 1.1.0 – October 11, 2025 =
- **Fixed**: Fixed-height mode now uses the tallest (max-content) slide to set a uniform slider and background-image height across all Flexi Content items, resolving the navigation “bottom jump.

= 1.0.10 – October 02, 2025 =
- **Added**: Accessibility improvements — navigation arrows.
- **Fixed**: Height unit support (px, %, vh, vw, em, rem) and range validation for min/height.
- **Fixed**: Auto-height alignment issue causing content/navigation jump.

= 1.0.9 – September 29, 2025 =
- **Added**: Mimmum height option for all blocks  
- **Fixed**: Minor CSS issues  

= 1.0.8 – September 20, 2025 =
- **Added**: Auto height option for all blocks  
- **Added**: Responsive support for navigation and pagination  
- **Fixed**: Minor CSS issues  
- **Enhanced**: Settings layout design

= 1.0.7 – July 27, 2025 =
- **Added**: Base Slider Content Padding Options
- **New**: Introduced **"Flexi Content"** block support inside sliders using `gslider-blocks/flexi-content-item` with dynamic InnerBlocks integration.

= 1.0.6 – July 16, 2025 =
- **Fixed**: Resolved deprecated code issues to ensure compatibility with the latest standards.
- **Fixed**: Corrected navigation padding issues for improved layout consistency.

= 1.0.5 – July 07, 2025 =
Fixed navigation position issues

= 1.0.4 July 02 2025 =
Added: Custom Navigation Icon option for sliders.
Users can now set custom left and right navigation icons from the Navigation settings for improved design flexibility.

Added: Support for custom icon images or SVG uploads.
Users can upload their own image files or SVG icons as navigation arrows to better match their site branding and design systems.

= 1.0.3 June 27 2025 =
Enhancements:

Updated the Navigation settings panel for greater layout flexibility.

Added Position Type toggle with options: Inside and Outside.

Introduced Arrow Direction dropdown (e.g., Row, Column) for custom navigation layout.

New Offset Controls: Added adjustable Top, Right, Bottom, and Left Offset sliders with support for both % and px units.

Added Gap setting to control spacing between navigation elements, with unit selection and reset capability.

Added Padding control to define spacing around the entire navigation component.

UI Improvements:

Enhanced slider responsiveness and alignment for a smoother user experience.

Grouped controls more logically to improve usability and clarity.

= 1.0.2 June 21 2025 =
New Features:
Element Toggles: Added options to show/hide Subtitle, Title, Description, Button, Navigation, and Pagination directly from the General tab.

Style Sections: New grouped style controls for each element (Title, Subtitle, Button, etc.) for easier customization.

Advanced Options: Added Layout, Background, Border & Shadow, and Custom CSS sections under the Advanced tab.

Improvements:
Unified Control Panel: Settings are now context-aware and cleaner, combining all slider options into one organized panel.

Better UX: Enhanced slider item management and updated tab structure (General, Style, Advanced) for smoother workflow.

Replaced the old Z-index-only Advanced tab with full design control options.

Retired the basic layout for a modern, component-based settings UI.

= 1.0.0 =
Initial release
