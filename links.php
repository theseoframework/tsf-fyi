<?php

$timer = microtime( true );

header( 'Cache-Control: max-age=3600', true );
header( 'Pragma: public' );
header( 'X-Robots-Tag: noindex, follow', true );

$json = json_decode(
	file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . 'links.json', false, stream_context_create( [ 'http' => [ 'timeout' => 1 ] ] ) )
	?: []
);

if ( ! $json ) {
	header( 'Cache-Control: max-age=30', true );
	http_response_code( 503 );
	printf(
		'Something went wrong parsing the links. Try again later. %s',
		sprintf( 'Generation ID: %s', bin2hex( random_bytes( 8 ) ) )
	);
	exit;
}

function loop_json( stdClass $json, array $basepoints = [], int $depth = 0 ) {
	foreach ( $json as $_endpoint => $_json ) {
		if ( is_string( $_json ) ) {
			yield from make_link( $basepoints, $_endpoint, $_json, $depth );
		} else {
			if ( $_json->_default ?? '' ) {
				yield from make_link( $basepoints, $_endpoint, $_json->_default, $depth );
			}
			foreach ( $_json->_alt ?? [] as $_alt ) {
				if ( $_json->_default ?? '' ) {
					yield from make_alt_link( $basepoints, $_alt, $_endpoint, $depth + 1 );
				} else {
					yield from make_alt_link( $basepoints, $_alt, $_endpoint, $depth );
				}
			}
			if ( isset( $_json->_deep ) ) {
				yield from loop_json( $_json->_deep, array_merge( $basepoints, [ $_endpoint ] ), $depth + 1 );
			}
		}
	}
}

function make_link( array $basepoints, string $endpoint, string $link, int $depth ) {
	$endpoint = false !== strpos( $endpoint, '$' ) ? sprintf( '<em class="noselect underline">%s</em>', $endpoint ) : $endpoint;
	yield sprintf(
		'%s<strong data-type=link>%s</strong>',
		$depth ? sprintf( '<span class=noselect>%s&mdash; </span>', str_repeat( '&emsp; ', $depth - 1 ) ) : '',
		'https://tsf.fyi/' . ltrim( implode( '/', $basepoints ) . "/$endpoint", '/' )
	)
	=> $link;
}

function make_alt_link( array $basepoints, string $altendpoint, string $altfor, int $depth ) {
	yield sprintf(
		'%s<span data-type=link>%s</span>',
		$depth ? sprintf( '<span class=noselect>%s</span>', str_repeat( '&emsp; ', $depth ) ) : '',
		'https://tsf.fyi/' . ltrim( implode( '/', $basepoints ) . "/$altendpoint", '/' )
	)
	=> sprintf(
		'<em>%s</em>',
		'https://tsf.fyi/' . ltrim( implode( '/', $basepoints ) . "/$altfor", '/' )
	);
}

$icon = <<<'ICON'
<svg class="icon" x="0px" y="0px" viewBox="0 0 275 275" shape-rendering="geometricPrecision"><g>
	<path class="path1" d="M274.516,185.634c0-1.09-0.242-1.453-1.453-1.453c-15.137,0-30.394,0-45.531,0
		c-1.09,0-1.453-0.242-1.453-1.453c0-15.137,0-30.394,0-45.531c0-1.211-0.363-1.453-1.453-1.453c-6.539,0-13.078,0-19.617,0
		c-6.539,0-13.078,0-19.617,0c-1.09,0-1.453,0.242-1.453,1.453c0,15.137,0,30.394,0,45.531c0,1.09-0.242,1.453-1.453,1.453
		c-15.137,0-30.394,0-45.531,0c-1.211,0-1.453,0.363-1.453,1.453c0,6.539,0,13.078,0,19.617c0,6.539,0,13.078,0,19.617
		c0,1.09,0.242,1.453,1.453,1.453c15.137,0,30.394,0,45.531,0c1.09,0,1.453,0.242,1.453,1.453c0,15.137,0,30.394,0,45.531
		c0,1.211,0.363,1.453,1.453,1.453c6.539,0,13.078,0,19.617,0c6.539,0,13.078,0,19.617,0c1.09,0,1.453-0.242,1.453-1.453
		c0-15.137,0-30.394,0-45.531c0-1.09,0.242-1.453,1.453-1.453c15.137,0,30.394,0,45.531,0c1.211,0,1.453-0.363,1.453-1.453
		c0-6.539,0-13.078,0-19.617C274.516,198.712,274.516,192.173,274.516,185.634z"></path>
	<path class="path2" d="M274.516,106.318c0-13.683,0-27.367,0-41.171c0-3.027,0-6.176,0-9.203c0-0.605-0.121-1.09-0.727-1.574
		c-1.332-1.09-2.543-2.301-3.754-3.391c-4.844-4.359-9.566-8.84-14.41-13.199c-6.902-6.297-13.805-12.594-20.707-18.769
		c-4.965-4.48-9.93-8.961-14.773-13.441c-2.18-1.937-4.48-3.754-6.418-6.055c-66.964,0-134.049,0-201.013,0
		c-1.09,0.121-1.574,0.242-2.301,0.605C7.266,1.331,4.844,3.39,2.906,6.175C1.332,8.476,0.242,10.897,0,13.561
		c0,82.101,0,164.322,0,246.423c0.242,0.484,0,0.969,0.242,1.453c0.121,0.363,0.121,0.605,0.242,0.969
		c0.605,2.785,1.574,5.328,3.512,7.387C7.266,273.547,11.746,275,16.59,275c23.492,0,47.105,0,70.597,0l0,0c1.09,0,2.18,0,3.269,0
		c0.121,0,0.242,0,0.363,0l0,0c23.976,0,47.953,0,71.929,0c0.363,0,0.605,0,0.969,0c0.605,0,0.727-0.242,0.727-0.848
		c0-8.719,0-17.558,0-26.277c0-1.695,0-1.695-1.816-1.695c-23.855,0-47.71,0-71.687,0l0,0c-20.101,0-40.203,0-60.425,0
		c-1.816,0-1.574,0.121-1.574-1.574c0-71.445,0-142.889,0-214.334c0-1.816-0.242-1.695,1.695-1.695c31.968,0,64.058,0,96.026,0
		c25.429,0,50.859,0,76.288,0c0.848,0,1.453,0.121,2.059,0.727c1.937,1.816,3.996,3.633,5.934,5.449
		c7.266,6.539,14.41,13.199,21.676,19.738c4.238,3.875,8.355,7.75,12.594,11.625c0.848,0.727,1.09,1.453,1.09,2.422
		c0,12.594,0,25.187,0,37.781l0,0c0,18.89,0,37.66,0,56.55c0,1.574,0,1.574,1.574,1.574c8.355,0,16.711,0,25.066,0
		c1.574,0,1.574,0,1.574-1.695C274.516,143.978,274.516,125.209,274.516,106.318L274.516,106.318z"></path>
</g></svg>
ICON;

?><!DOCTYPE html>
<html>
	<head>
		<title>TSF.fyi links</title>
		<meta name=viewport content="width=device-width, initial-scale=1" />
		<style>
			body {
				min-height: 100vh;
				display: flex;
				flex-direction: column;
				justify-content: center;
				background: #919ea1;
				font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
				font-size: 16px;
				line-height: 1.625em;
				color: #333;
				margin: 0
			}
			a {
				color: inherit;
				text-decoration: none
			}
			a:focus {
				outline: none;
				text-decoration: underline
			}
			main {
				color: #070d03;
				padding: 2em;
				background: #e3e7e8;
				border: 1px solid #465053;
				margin: 1em auto;
				border-radius: 3px;
				box-shadow: 0 0 1em #465053;
				max-width: 1400px
			}
			footer {
				text-align: center;
				color: #fdfbfe;
				margin: 1em auto;
				max-width: 1400px;
				font-size: .875em
			}
			h1 {
				margin-top: 0
				font-family: Verdana,Geneva,sans-serif;
				font-size: 25px;
				font-weight: 400;
				line-height: 26px; /* 25+1 svg overflow clip fix */
				margin: 0
			}
			h1 a {
				vertical-align: top
			}
			h1 a:hover,
			h1 a:focus {
				color: inherit
			}
			h1 svg {
				display: inline-block;
				padding: 0;
				margin-right: 7px;
				margin-left: 0;
				vertical-align: bottom;
				width: 1em;
				height: 1em;
				fill: currentColor;
				line-height: 1em
			}
			h1 a:hover .path1 {
				fill: #0ebfe9
			}
			table, td, th {
				border: 1px solid #969a93
			}
			table {
				border-collapse: collapse;
				width: 100%;
				overflow: auto
			}
			th {
				text-align: left
			}
			th, td {
				padding: .25em .5em
			}
			.noselect {
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none
			}
			.underline {
				text-decoration: underline
			}
		</style>
	</head>
	<body>
		<main>
			<h1><a href=https://tsf.fyi/links.php><?php echo $icon; ?>TSF.fyi</a> registered endpoints</h1>
			<p>
				<em><strong>$</strong> = single directory wildcard. <strong>$$</strong> = unlimited directory wildcard.</em><br>
				<em>Bolded endpoints are canonical, others are alternatives; either may trickle down for deeper links.</em>
			</p>
			<table>
				<tr><th>Endpoint</th><th>Link</th></tr>
				<?php
				foreach ( loop_json( $json ) as $_endpoints => $_result ) {
					vprintf(
						"\t\t\t\t<tr><td>%s</td><td>%s</td></tr>\n",
						[
							$_endpoints,
							$_result
						]
					);
				}
				?>
			</table>
		</main>
		<script>
			(()=>{
				let blocked = false;
				document.addEventListener( 'copy', ( event ) => {
					blocked || event.clipboardData.setData( 'text/plain', document.getSelection().toString() );
					event.preventDefault();
				} );
				document.body.addEventListener( 'dblclick', function( event ) {
					blocked = false;
					if ( ! event.target.dataset || ! event.target.dataset.type ) return;
					if ( 'link' === event.target.dataset.type ) {
						event.preventDefault();

						let range     = document.createRange(),
							selection = window.getSelection();

						if ( selection.rangeCount > 0 )
							selection.removeAllRanges();

						range.selectNode( event.target );
						selection.addRange( range );
						document.execCommand( 'copy' );

						let oldHTML = event.target.innerHTML;

						setTimeout( () => {
							blocked = true;
							event.target.innerHTML = 'Copied to clipboard!';
							setTimeout( () => {
								event.target.innerHTML = oldHTML;
								blocked = false;
							}, 1000 );
						}, 200 );

					}
				} );
			})();
		</script>
		<footer>
			<p>
			<?php
			vprintf(
				'Powered by %s &bull; Generated in %s microseconds &bull; Generation ID: <code>%s</code>',
				[
					'<a href=https://theseoframework.com/>The SEO Framework</a>',
					(int) abs( ( microtime( true ) - $timer ) * 1e6 ),
					bin2hex( random_bytes( 16 ) ),
				]
			);
			?>
			</p>
		</footer>
	</body>
</html>
<?php
