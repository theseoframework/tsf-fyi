<?php

/**
 * TSF.fyi redirection interpreter.
 * Copyright (C) 2019 Sybre Waaijer, The SEO Framework (https://theseoframework.com/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( empty( $_SERVER['SCRIPT_URI'] ) ) {
	http_response_code( 503 );
	exit;
}

header( 'Cache-Control: max-age=3600', true );
header( 'Pragma: public' );

if ( ( $_GET['error'] ?? false ) ) {
	header( 'X-Robots-Tag: noindex, follow', true );
	header( 'Cache-Control: max-age=60', true );
	http_response_code( 503 );
	require __DIR__ . DIRECTORY_SEPARATOR . '503.html';
	exit;
}

function retry(): void {
	$retry = (int) ( $_GET['r'] ?? 0 ) + 1;

	if ( $retry > 3 ) {
		$location = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/";
		$error    = json_last_error() ?: -1;

		header( 'X-Redirect-By: tsf.fyi' );
		header( 'X-Retry-Request: true' );
		header( "Location: $location?error=$error", true, 302 );
		exit;
	}

	// Redirect after 250ms.
	usleep( 2.5e5 );
	header( 'X-Redirect-By: tsf.fyi' );
	header( 'X-Retry-Request: true' );
	header( "Location: {$_SERVER['SCRIPT_URI']}?r=$retry", true, 302 );
	exit;
}

$json = json_decode(
	file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . 'links.json', false, stream_context_create( [ 'http' => [ 'timeout' => 1 ] ] ) )
	?: []
) or retry();

$request = preg_replace(
	'/[^a-z0-9_\/%\-]/',
	'',
	strtolower( substr_replace( $_SERVER['REQUEST_URI'], '', 0, strlen( dirname( $_SERVER['PHP_SELF'] ) ) ) )
) ?: 'links';

$r = array_values( array_filter( explode( '/', $request ) ) );

/**
 */
function find_endpoint( stdClass $json, array $r ): string {

	$default = '';
	$depth   = 0;

	while ( isset( $r[ $depth ] ) ) {

		$next = $json->{$r[ $depth ]} ?? null;

		if ( null === $next ) {
			foreach ( $json as $_endpoint => $_json ) {
				if ( isset( $_json->_alt ) && in_array( $r[ $depth ], $_json->_alt, true ) ) {
					$next = $json->{$_endpoint};
					break;
				} elseif ( is_string( $_json ) ) {
					if ( false !== strpos( $_endpoint, '$' ) ) {
						if ( false !== strpos( $_endpoint, '$$' ) ) {
							// Wildcard replacement.
							$_items = array_slice( $r, $depth );
							$next = str_replace( '$$', implode( '/', $_items ), $_json );
						} else {
							// Prudent replacement.
							$next = str_replace( '$', $r[ $depth ], $_json );
						}
					}
				}
			}
		}

		if ( isset( $next->_deep ) ) {
			$default = $next->_default ?? $default;
			$json    = $next->_deep;
			++$depth;
		} else {
			if ( is_string( $next ) ) {
				$endpoint = $next;
			} elseif ( $next ) {
				$endpoint = $next->_default ?? $default;
			} else {
				// Nothing found, always abort to prevent unwanted recursion and DDoS.
				break;
			}
			// We're done here.
			break;
		}
	}

	return $endpoint ?? $default;
}

$location = find_endpoint( $json, $r ) ?: '';

if ( ! $location ) :
	header( 'X-Robots-Tag: noindex, follow', true );
	http_response_code( 404 );
	require __DIR__ . DIRECTORY_SEPARATOR . '404.html';
	exit;
endif;

http_response_code( 301 );
header( 'X-Redirect-By: tsf.fyi' );
header( "Location: $location", true, 301 );
exit;
