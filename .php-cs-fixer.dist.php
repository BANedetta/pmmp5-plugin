<?php

return (new PhpCsFixer\Config())
	->setRules([
		"@PSR2" => true,
		"indentation_type" => true,
		"ordered_imports" => [
			"sort_algorithm" => "alpha",
			"imports_order" => ["class", "function", "const"],
		],
		"no_unused_imports" => true,
		"array_syntax" => ["syntax" => "short"],
		"no_trailing_whitespace_in_comment" => true,
		"no_empty_comment" => true,
		"no_extra_blank_lines" => ["tokens" => ["extra"]],
		"phpdoc_to_comment" => false,
		"no_trailing_whitespace" => true
	])
	->setIndent("\t")
	->setFinder(
		PhpCsFixer\Finder::create()->in(__DIR__)
			// ->exclude("vendor")
			// ->exclude("libs")
	);
