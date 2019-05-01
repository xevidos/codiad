# How to Contribute

Your contributions are welcome and we're very open about how contributions are made, however, to keep order to things please take the following into consideration:

* Check the issues to ensure that someone else isn't already working on the bug or feature
* Submit an issue for bugs and feature additions before you start with it
* Familiarize yourself with the documentation in the [Wiki](https://gitlab.com/xevidos/codiad/wikis/home)

There is an established format for `components` which utilizes one JS (`init.js`) and one CSS (`screen.css`) which is handled by the loader file. Any other resources used should be loaded or accessed from one of these.

**Don't Reinvent the Wheel!** There's an API and defined, easy-to-understand set of methods for a reason - use them.

Stick to the conventions defined in other components as closely as possible. 

* Utilize the same commenting structure
* Use underscores in namespaces instead of interCaps
* Use intend with a tab character in your code
* When working with the editor utilize the `active` object whenever possible instead of going direct to the `editor`

**Javascript Formatting**

In order to maintain a consistant code structure to the code across the application please follow the wordpress standard, or run any changes through [JSBeautifier] (http://jsbeautifier.org/) with the settings below.

	{
		"indent_size": "1",
		"indent_char": "\t",
		"max_preserve_newlines": "5",
		"preserve_newlines": true,
		"keep_array_indentation": true,
		"break_chained_methods": false,
		"indent_scripts": "normal",
		"brace_style": "collapse",
		"space_before_conditional": false,
		"unescape_strings": false,
		"jslint_happy": false,
		"end_with_newline": true,
		"wrap_line_length": "0",
		"indent_inner_html": true,
		"comma_first": false,
		"e4x": false
	}

If you have questions, please ask. Submit an issue or [contact us directly](mailto:support@telaaedifex.com). 

**PHP Formatting**

In order to maintain a consistant code structure we follow WordPress standards.
