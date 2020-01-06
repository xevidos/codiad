# How to Contribute

Your contributions are welcome and we're very open about how contributions are made; however, to keep order to things please take the following into consideration:

* Check the issues to ensure that someone else isn't already working on the bug or feature
* Submit an issue for bugs and feature additions before you start with it
* Familiarize yourself with the documentation in the [Wiki](https://gitlab.com/xevidos/codiad/wikis/home)

There is an established format for `components` which utilizes one JS (`init.js`) and one CSS (`screen.css`) which is handled by the loader file. Any other resources used should be loaded or accessed from one of these.

**Don't Reinvent the Wheel!** There's an API and a defined, easy-to-understand set of methods for a reason - use them.

Stick to the conventions defined in other components as closely as possible. 

* Utilize the same commenting structure
* Use underscores in namespaces instead of camelCase
* Indent using tabs
* When working with the editor utilize the `active` object whenever possible instead of going direct to the `editor`

**Javascript Formatting**

In order to maintain a consistent code structure to the code across the application, please follow the WordPress standard, or run any changes through [JSBeautifier] (http://jsbeautifier.org/) with the settings below.

	{
		"brace_style": "collapse",
		"break_chained_methods": false,
		"comma_first": false,
		"e4x": false,
		"end_with_newline": true,
		"indent_char": "\t",
		"indent_empty_lines": true,
		"indent_inner_html": true,
		"indent_scripts": "normal",
		"indent_size": "1",
		"jslint_happy": false,
		"keep_array_indentation": true,
		"max_preserve_newlines": "5",
		"preserve_newlines": true,
		"space_after_anon_function": false,
		"space_after_named_function": false,
		"space_before_conditional": false,
		"space_in_empty_paren": false,
		"space_in_paren": true,
		"unescape_strings": false,
		"unindent_chained_methods": true,
		"wrap_line_length": "0"
	}

If you have questions, please ask. Submit an issue or [contact us directly](mailto:support@telaaedifex.com). 

**PHP Formatting**

In order to maintain a consistent code structure we follow WordPress standards.
