=== FaeCursor – Interaction Effects Toolkit ===
Contributors: psakhilsoman, faecursor
Author: FaeCursor Plugin Team
Version: 1.2.2
Plugin URI: https://faecursor.com/
Tags: cursor, cursor plugin, custom cursor, mouse plugin, wordpress cursor, mouse, pointer
Requires at least: 5.6
Tested up to: 6.9
Stable tag: 1.2.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bring your WordPress site to life with interactive cursor, keyboard, and screen effects — built for smooth performance and full control.

== Description ==

FaeCursor adds interactive cursor, keyboard, and screen effects to your WordPress website, designed for engaging visual feedback without compromising performance.

Create smooth trails, sparkles, particles, and subtle motion effects that enhance user interaction while keeping your site lightweight and responsive.

Unlike effect packs that load everything globally, FaeCursor uses a structured module system so you can enable only what you need.

== Modular Architecture ==

FaeCursor is built as a controlled interaction system, not a collection of random animations.

= Cursor Effects =
Add refined visual feedback that follows pointer movement. Includes multiple presets with adjustable size, speed, and color controls.

= Keyboard Effects =
Display subtle interaction feedback during typing. Ideal for forms, comment sections, and interactive experiences.

= Screen Effects =
Optional screen-based visual layers such as particle systems and motion backgrounds that enhance atmosphere without interfering with usability.

Each module can be enabled or disabled independently.

== Key Features ==

• Independent interaction layers  
• Conditional asset loading (disabled modules do not load scripts)  
• Real-time live preview in the admin dashboard  
• Adjustable performance and animation controls  
• Mobile and touch device filtering  
• Compatible with Elementor, Divi, Bricks, Gutenberg, and any WordPress themes  
• Lightweight and optimized for modern browsers  

== Why FaeCursor? ==

FaeCursor focuses on performance, control, and clean integration:

• Enable only the effects you need  
• Avoid unnecessary frontend bloat  
• Maintain compatibility with modern themes and builders  
• Keep full control over visual behavior  

== FaeCursor Pro ==

FaeCursor Pro extends the toolkit with advanced control options:

• Page-level and post-level targeting  
• Role-based visibility rules  
• Extended customization controls (size, speed, opacity, triggers)  

Want even more control over your interaction effects?  
Explore everything FaeCursor Pro has to offer: [FaeCursor Pro](https://faecursor.com/)

== Installation ==

1. Upload the `faecursor` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the FaeCursor settings page in your WordPress dashboard to configure effects.

== Frequently Asked Questions ==

= Will FaeCursor slow down my site? =

No. Only enabled modules load their assets. Disabled effects do not enqueue scripts or styles.

= Can I disable effects on mobile devices? =

Yes. You can disable specific modules on touch devices while keeping others active.

= Does it work with page builders? =

Yes. FaeCursor works with Elementor, Divi, Bricks, Gutenberg, and most any WordPress themes.

== Screenshots ==

1. **Plugin Settings Screen** screenshot-1.png
2. **Sparkle Effect** screenshot-2.png
3. **Keyboard Interaction Effects** screenshot-3.png
4. **Screen Particle Effects** screenshot-4.png

== Changelog ==

= 25 Feb 2026 - ver 1.2.2 =
* Fixed Duo Circle and Duo Circle 2 cursor effects so the secondary circle no longer sticks or animates from an old position after using the browser back button.
* Improved page restore handling for cursor effects to work smoothly with the browser back/forward cache.

= 20 Feb 2026 - ver 1.2.1 =
* Improved WordPress Coding Standards compliance for better code quality.
* Enhanced security with proper input sanitization and nonce verification.
* Fixed compatibility issues for WordPress.org submission.
* Code optimization and performance improvements.

= 05 Feb 2026 - ver 1.2.0 =
* Updated admin UI with enhanced usability and live preview.
* Added new cursor, keyboard, and screen effects.
* Optimized performance for modern browsers and WordPress versions.

= 25 Oct 2025 - ver 1.1.0 =
* Updated admin UI for a more intuitive settings experience.
* Added new cursor icons for greater customization.
* Fixed Safari browser issue with the dual circle cursor effect.

= 23 Oct 2024 - ver 1.0.0 =
* Initial release of FaeCursor.
* Added star trail, sparkle, and magic aura effects.

== Upgrade Notice ==

= 1.2.2 =
Fixes Duo Circle cursor behavior after navigating back in the browser; recommended for sites using these effects.

= 1.2.1 =
Security and code quality improvements. Adds smart review request system. Recommended update for WordPress.org compliance.

= 1.2.0 =
This update adds keyboard and screen interaction effects with a fully improved admin UI for live previews.

= 1.1.0 =
Refreshed admin UI, new icons, and Safari dual circle fix. Update for the improved experience.

= 1.0.0 =
Initial release. Enjoy interactive cursor effects on your WordPress site!

== License ==

This plugin is licensed under the GPLv2 or later.