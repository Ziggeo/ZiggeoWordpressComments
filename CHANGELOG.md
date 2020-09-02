This file contains the change log info for the `Ziggeo` (Ziggeo Core) plugin.

= 2.4.4 =
* Improved widgets parser to help when not all of the expected data is passed to it. Videos received from a widget that did not pass all details will be marked with "no_id" tag.

= 2.4.3 =
* Improved tags parsing where wrong type of quote was used causing PHP Notice being raised about it. Thank you Igor for reporting it.
* Added improvment to widgets parsing where hooks used fired while the expected core functionality was not available. The imporvement makes the code work only when it is available.

= 2.4.2 =
* Notifications prune and clear are set to be disabled on click, to prevent clicking on the same multiple times while waiting for the server to respond.
* Added the option for default player for the integrations allowing you to simply choose the template you wish to use right from the settings.
* Added a change in how we do parsing so that the content does not "move" where before it could end up "moving" itself out of place in certain scenarios.
* Added support for parsing templates in widget title and widget content. All videos recorded within the widget, will also get a "widget", "widget_title" or "widget_content" tag as well as the unique ID of the widget, allowing you to easily find those videos.
* Added location info when parsing custom tags, in case that is needed when parsing

= 2.4.1 =
* Note: Please check changelog for 2.4 if you are updating from older version
* Moved some functions to resolve the issue where people using bbPress would have an error instead of toolbar shown.

= 2.4 =
* Added support for do_shortcodes() for all core template types
* New constant used when shortcodes are run ZIGGEO_SHORTCODE_RUN allowing you to check for the same (since some actions and hooks might be sending you different info or will not fire).
* Added a new way of showing addons as well as to make it easy for you to add your own through our new Addons page. Reach out to us to have your plugin 'advertised' in our plugin.
* Added initial support for the PHP SDK page once included. Preparing to add it in a way that does not stop anyone
* Added logo for our addons listing
* Made it easier to recognize the videos of different status per Natasha's recommendation
* Fixed typo spotted by Natasha that cause your own hooks to not make much of a difference in the code output. Affecting those using `ziggeo_get_template_recorder_integrations` and `ziggeo_get_template_player_integrations` filters.
* Added new user tags for easy substitute in templates per Karan's suggestion. You can now use `%USER_ID%`, `%USER_NAME_FIRST%`, `%USER_NAME_LAST%`, `%USER_NAME_FULL%`, `%USER_NAME_DISPLAY%`, `%USER_EMAIL%` and `%USER_USERNAME%` in your templates.
* Added `ziggeo_p_integrations_field_add_custom_tag` to help add custom attributes / codes within the embedding code. Useful to add class, id or some other attribute and you have the parsed template code. Mostly for integrations.
* Fixed an issue where the advanced editor would not add the parameter into the editor nor select the value if one was present.
* Added buttons to prune and clear the notifications for administrators. Prune allows you to remove all duplicates so you can see only single notification about something, while clear just clears them all out.
* Added the options for version and revision into the settings section (under Expert Settings) allowing easier change of both version and revision.

= 2.3.4 =
* Dropdown values in the dashboard reflected true state of settings, however the values were in wrong format, causing some settings to not be added to the page. This way they now are.

= 2.3.3 =
* Changed the way we output scripts to page in order to work better in different pages when specific setup is present that would cause codes to be out before they should be.

= 2.3.2 =
* Added a wait for ZiggeoApi just in case when due to load it might cause error.

= 2.3.1 =
* Added filters when retrieving the default codes for player and recorder, allowing you to dynamically modify them
* Added Notifications system and admin page allowing plugin to report things to admin
* Introduced new way we handle plugin settings
* Fixed a bug on REST requests - thank you Maxie C.
* Added Video Listing page for admins to see all videos, sort them and moderate them
* Added global AJAX request to allow us to detect for videos recorded on WP pages. Available as a hook for your code.
* IMPORTANT: Removed support for videowalls as announced since 2.0 Please use Videowalls plugin instead
* Added better support for AJAX requests
* Added auth init option
* Added a field for server auth token
* Added option for screen recording and `ziggeo_echo_application_settings` hook to add options for Ziggeo application initialization even if they are not yet supported by the Wordpress plugin.
* Fixed validation error that would clear some values
* Fixed comments error that would happen with v2.3
* Fixed defaults recorder typos which would result in recorder not being shown

= 2.2 =
* Template names fix - The names will be saved as lowercase, names will be checked so empty names are not present
* Additional functions to help other plugins to integrate with (get assets, clean values, etc.)
* Tested to work with our plugins that integrate to Gravity Forms, WPForms, Videowalls, bbPress, Job Manager
* Better handling for your own template designs in editor

= 2.1 =
* Fixing SVN issue
* Few minor updates to the code

= 2.0 =
* Overhaul bringing new possibilities
* Hooks and examples of how to use them
* Support for very latest of Ziggeo

= 1.15 =
* Added option to have integrations to other plugins
* New tab is added so you can easily manage the integration modules
* Added first integration - to Gravity Forms - by adding templates to your Gravity Forms form (per your setup)
* Integrations structure allows you to easily create your own integration modules and activate them through the plugin settings

= 1.14 =
* Added option to show videos from different posts in a video wall
* added option to show multiple types of videos (approved,rejected,pending) instead of single status.

= 1.13 =
* Fixed a small bug where additional spaces after template name would not allow you to delete/edit it easily
* Added VideoWall template with its various parameters to set it all up
* Made tinyMCE button load up when editing is done by Contributer or higher (in case the toolbar is shown in public)

= 1.12 =
* Fixed issue with double quotes stopping TinyMCE button in toolbar to not work properly
* Fixed issue with double quotes breaking template editing option
* Fixed template parsing in comments to allow template to be used and custom parameters
* Added option to remove Ziggeo Video Aid button from TinyMCE toolbar
* Modified templates builder layout

= 1.11 =
* Templates tab added to the plugin to expand its possibilities/features
* Previous setup now set as global defaults / fallback for templates
* Added more control for video recording and playback in comments 
* Added TinyMCE button in toolbar for easier usage of your templates

= 1.10 =
* Options to enable/disable video/text comments

= 1.9 =
* Use newest version of the Ziggeo assets

= 1.8 =
* Allow video posts from namespaced urls

= 1.7 =
* Allows integration of recorder in blog posts by using [ziggeo][/ziggeo]

= 1.6 =
* Improved Compatability with different commenting systems

= 1.5 =
* Enabled WebRTC and Resumable Uploads

= 1.4 =
* Allow admin to specify Ziggeo options

= 1.3 =
* Allow users to upload videos

= 1.2 =
* Allow to record multiple videos in a post
* Allow to combine videos with text in a post

= 1.1 =
* Fix comments for non-standard themes

= 1.0 =
* First version