=== Plugin Name ===
Contributors: zacharyfox
Tags: posts, syntax highlight, vim
Requires at least: 2.2.2
Tested up to: 2.3.2
Stable tag: trunk

Vim Color Improved is a syntax highlighting plugin that uses vim.

== Description ==

Vim Color Improved is a syntax highlighting plugin that allows you to include code from local or reomte files in your Wordpress posts.

It uses the same tag and parameter parsing as the popular CodeViewer 1.4, and should be compatible with it's options. In addition, any of the optional parameters from codeviewer 1.4 can be set as defaults, which can then be overriden by parameters in the tag. Vim Color Improved outputs code in &ltpre&gt formatted blocks, rather than ordered lists, which can be difficult to copy and paste, and can syntax highlight any language which vim supports.

Vim Color Improved contains a sophisticated caching system that stores the generated html to the filesystem. This greatly reduces the time required to display the code. In addition, it checks the modified time on both local and remote files to ensure that cached information is up-to-date. If it is unable to access the source code, and there is a cached version available, it will display the cached version with a notice.

Using Vim Color Improved

(These instructions basically parallel those for CodeViewer 1.4)

Vim Color Improved searches your post for a custom tag named [viewcode ] [/viewcode], that tells the server to look at an external file and parse it into syntax higihlighted html. It can be placed anywhere a block-level tag is valid but the tag must be properly closed.

Note that there should not be a white space character after viewcode and before ].

*Parameters
[viewcode] src="URI or path to local file" link=yes|no lines= scroll=yes|no scrollheight=valid css height showsyntax=yes|no cache=yes|no[/viewcode]

Default values for all of these parameters, other than src can be set in the options page.

The src attribute is required. 
src - string - The URI or path to a local file of the code to display. Note that relative paths are in relation to the default_path set in the options page. This default value is set to the directory your blog is installed in.

The link attribute is optional. 
link - string - Should the link to the code be displayed (yes), or not be displayed (no). If the link attribute is left out of the tag completely, the value defaults to no.

The lines attribute is optional. 
lines - string - Which linenumbers shall be visible in the output. Use , and - to separate linenumbers. Example: lines=1,3-5,10-12,16-18,22.

The scroll attribute is optional. 
scroll - string - Should the scrollbar be displayed (yes), or not be displayed (no).

The scrollheight attribute is optional. 
height - string - Height of the scrollbar. Any valid css height declaration can be used. Example: 100px or 50em

The showsyntax attribute is optional. 
showsyntax - string - Should the syntax used of [viewcode ]  be displayed (yes), or not be displayed (no).

*Additional Parameters

These are new parameters that can be used by Vim Color Improved.

The cache attribute is optional.
cache - string - Cache this code block (yes) or not (no).

The html_use_css attribute is optional.
html_use_css - string - This is a parameter which is passed to vim, and affects the html that is output. Use css (yes) or not (no). If you chose to not use css, the code will be output in &ltspan&gt tags with a style="color:#xxxxxx" attribute, rather than a class.

All attribute values can optionally be surrounded with double quotes (") or single quotes(').

== Installation ==

1. Download vim-color-improved-x.x.x.zip.
2. Unzip the archive and copy the entire vim-color-improved folder to the wp-content/plugins directory
3. Activate the plugin from the Plugins page in your WordPress administration console.
4. Vim Color Improved also provides an options page for you to set the default options. While the plugin will work without any intervention, you may wish to review these at (Options->Vim Color Improved. You may also see a list of cached files and clear the cache there.

== Frequently Asked Questions ==

= Why are there no FAQs? =

This is the first release.

== Screenshots ==

1. Here is a screenshot of Vim Color Improved in action. We can see here the parameters that were passed in the tag by looking at the showsyntax block above the html code block.

== Requirements ==

This plugin may not work on all php installations. Specifically, there are some access needs that may be locked down on your webserver.

1. Your webserver must be able to exec(vim) through php
2. If you want to use remote files, your webserver must be able to open the files through http using file()

== To Do List ==

1. Add the ability to use vim's options, such as using css, using html, etc...
2. Add the ability to use WYSIWYG editor for posts, including file selection box for local files.

== Version History ==

v.0.4.0 Bug fixes and new features

- Fixed problem with files not being found not displaying an error
- Fixed vim command, was missing last quit
- Added vci_html_use_css parameter and option
- Added vim classes to style.css
- Refactoring of vci_color(), created new methods to decrease the main method size
- Added vci_link for a default value
- Added more vim options to the vim command to help performance
- Added functions.php to include additional functions not directly related to vci
- Moved temporary directory to the system temp dir to ease installation - no longer need to chmod a directory
- Attempt auto-detect of vim path using exec('which vim')
- Added admin css, moved css files to css directory
- Added management page for cache management
- Added ability to clear single files from the cache
- Changed to scroll horizontally by default if code is too wide

v.0.3.2 First Public Version

== License ==
Copyright 2008  Zachary Fox  (email : ecommerceninja@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
