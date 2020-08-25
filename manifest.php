<?php

$manifest = array(
	
	"short_name" => "Codiad",
	"name" => "Codiad",
	"description" => "A code editor.",
	"icons" => array(
		array(
			"src" => "/images/icons-192.png",
			"type" => "image/png",
			"sizes" => "192x192"
		),
		array(
			"src" => "/images/icons-512.png",
			"type" => "image/png",
			"sizes" => "512x512"
		),
	),
	"start_url" => "",
	"background_color" => "#1a1a1a",
	"display" => "standalone",
	"scope" => "",
	"theme_color" => "#1a1a1a",
	"shortcuts" => array(),
);


exit( json_encode( $manifest ) );

?>