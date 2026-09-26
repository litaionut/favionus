/**
 * Hero LiDAR scan: spinning beam, lit particles, wind-speed labels.
 */
( function() {
	const svg = document.getElementById( 'lidar-scan' );
	if ( ! svg ) {
		return;
	}

	const particlesGroup = document.getElementById( 'lidar-particles' );
	const labelsGroup = document.getElementById( 'lidar-labels' );
	const beam = document.getElementById( 'lidar-beam' );
	if ( ! particlesGroup || ! labelsGroup || ! beam ) {
		return;
	}

	const EMITTER = { x: 240, y: 346 };
	const ELLIPSE = { cx: 240, cy: 70, rx: 140, ry: 30 };
	const HIT_RADIUS = 11;
	const LABEL_MS = 2000;
	const SPIN_MS = 4000;
	const FILL = '#d63d3d';
	const FILL_LIT = '#ffffff';

	const movers = [
		{ y: 48, r: 1.6, x0: 40, x1: 440, dur: 7200, delay: 1200 },
		{ y: 78, r: 1.3, x0: 28, x1: 452, dur: 6100, delay: 3400 },
		{ y: 108, r: 1.8, x0: 50, x1: 430, dur: 8000, delay: 800 },
		{ y: 138, r: 1.4, x0: 36, x1: 444, dur: 5400, delay: 2100 },
		{ y: 168, r: 2, x0: 60, x1: 420, dur: 6800, delay: 4600 },
		{ y: 198, r: 1.5, x0: 44, x1: 436, dur: 7500, delay: 1700 },
		{ y: 228, r: 1.2, x0: 70, x1: 410, dur: 5000, delay: 3000 },
		{ y: 258, r: 1.7, x0: 52, x1: 428, dur: 6400, delay: 5200 },
		{ y: 288, r: 1.4, x0: 80, x1: 400, dur: 4800, delay: 400 },
		{ y: 318, r: 1.6, x0: 90, x1: 390, dur: 5800, delay: 2800 },
		{ y: 92, r: 1.5, x0: 120, x1: 360, dur: 4400, delay: 1500 },
		{ y: 152, r: 1.1, x0: 30, x1: 450, dur: 9000, delay: 6000 },
		{ y: 212, r: 1.8, x0: 100, x1: 380, dur: 5600, delay: 2200 },
		{ y: 272, r: 1.4, x0: 64, x1: 416, dur: 7000, delay: 3800 },
	];

	const staticDots = [
		[ 56, 64, 1.4 ], [ 412, 56, 1.6 ], [ 38, 186, 1.2 ], [ 428, 174, 1.5 ],
		[ 72, 246, 1.3 ], [ 404, 238, 1.7 ], [ 140, 42, 1.2 ], [ 330, 36, 1.3 ],
		[ 188, 118, 1.5 ], [ 292, 126, 1.4 ], [ 160, 206, 1.2 ], [ 318, 198, 1.6 ],
		[ 176, 286, 1.3 ], [ 304, 274, 1.4 ], [ 214, 156, 1.1 ], [ 266, 148, 1.2 ],
		[ 228, 88, 1.3 ], [ 252, 96, 1.5 ], [ 200, 248, 1.2 ], [ 278, 236, 1.4 ],
	];

	const ns = 'http://www.w3.org/2000/svg';
	const particles = [];

	function addParticle( x, y, r, motion ) {
		const el = document.createElementNS( ns, 'circle' );
		el.setAttribute( 'cx', x );
		el.setAttribute( 'cy', y );
		el.setAttribute( 'r', r );
		el.setAttribute( 'class', 'lidar-particle' );
		particlesGroup.appendChild( el );

		const label = document.createElementNS( ns, 'text' );
		label.setAttribute( 'class', 'lidar-speed' );
		label.setAttribute( 'text-anchor', 'middle' );
		label.setAttribute( 'fill', FILL );
		label.setAttribute( 'font-size', '9' );
		label.setAttribute( 'font-family', 'JetBrains Mono, ui-monospace, monospace' );
		label.setAttribute( 'font-weight', '500' );
		label.setAttribute( 'opacity', '0' );
		labelsGroup.appendChild( label );

		particles.push( {
			el: el,
			label: label,
			x: x,
			y: y,
			r: r,
			baseR: r,
			motion: motion || null,
			until: 0,
			speed: '',
		} );
	}

	movers.forEach( function( m ) {
		addParticle( m.x0, m.y, m.r, m );
	} );
	staticDots.forEach( function( d ) {
		addParticle( d[ 0 ], d[ 1 ], d[ 2 ], null );
	} );

	function wrapProgress( now, dur, delay ) {
		return ( ( now + delay ) % dur ) / dur;
	}

	function distToSegment( px, py, ax, ay, bx, by ) {
		const abx = bx - ax;
		const aby = by - ay;
		const len2 = abx * abx + aby * aby;
		if ( len2 === 0 ) {
			return Math.hypot( px - ax, py - ay );
		}
		let t = ( ( px - ax ) * abx + ( py - ay ) * aby ) / len2;
		t = Math.max( 0, Math.min( 1, t ) );
		const qx = ax + t * abx;
		const qy = ay + t * aby;
		return Math.hypot( px - qx, py - qy );
	}

	function randomSpeed() {
		return ( 3 + Math.random() * 18 ).toFixed( 1 );
	}

	function tick( now ) {
		const angle = ( now / SPIN_MS ) * Math.PI * 2;
		const x2 = ELLIPSE.cx + ELLIPSE.rx * Math.cos( angle );
		const y2 = ELLIPSE.cy + ELLIPSE.ry * Math.sin( angle );
		beam.setAttribute( 'x2', x2 );
		beam.setAttribute( 'y2', y2 );

		particles.forEach( function( p ) {
			if ( p.motion ) {
				const u = wrapProgress( now, p.motion.dur, p.motion.delay );
				p.x = p.motion.x0 + ( p.motion.x1 - p.motion.x0 ) * u;
				p.el.setAttribute( 'cx', p.x );
			}

			const hit = distToSegment( p.x, p.y, EMITTER.x, EMITTER.y, x2, y2 ) <= HIT_RADIUS + p.baseR;

			if ( hit ) {
				p.el.setAttribute( 'fill', FILL_LIT );
				p.el.setAttribute( 'r', p.baseR * 2.2 );
				p.el.setAttribute( 'class', 'lidar-particle is-lit' );
				if ( now >= p.until ) {
					p.until = now + LABEL_MS;
					p.speed = randomSpeed() + ' m/s';
					p.label.textContent = p.speed;
				}
			} else {
				p.el.setAttribute( 'fill', FILL );
				p.el.setAttribute( 'r', p.baseR );
				p.el.setAttribute( 'class', 'lidar-particle' );
			}

			if ( p.until > now ) {
				p.label.setAttribute( 'x', p.x );
				p.label.setAttribute( 'y', p.y - 10 );
				p.label.setAttribute( 'opacity', '1' );
			} else {
				p.label.setAttribute( 'opacity', '0' );
			}
		} );

		window.requestAnimationFrame( tick );
	}

	window.requestAnimationFrame( tick );
}() );
