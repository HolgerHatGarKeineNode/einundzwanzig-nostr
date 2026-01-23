"use strict";
(self["webpackChunkvideos"] = self["webpackChunkvideos"] || []).push([[702],{

/***/ 702:
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VideoTexture: () => (/* binding */ VideoTexture)
/* harmony export */ });
/* harmony import */ var _constants_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9128);
/* harmony import */ var _Texture_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8280);



/**
 * A texture for use with a video.
 *
 * ```js
 * // assuming you have created a HTML video element with id="video"
 * const video = document.getElementById( 'video' );
 * const texture = new THREE.VideoTexture( video );
 * ```
 *
 * Note: When using video textures with {@link WebGPURenderer}, {@link Texture#colorSpace} must be
 * set to THREE.SRGBColorSpace.
 *
 * Note: After the initial use of a texture, its dimensions, format, and type
 * cannot be changed. Instead, call {@link Texture#dispose} on the texture and instantiate a new one.
 *
 * @augments Texture
 */
class VideoTexture extends _Texture_js__WEBPACK_IMPORTED_MODULE_0__/* .Texture */ .g {

	/**
	 * Constructs a new video texture.
	 *
	 * @param {HTMLVideoElement} video - The video element to use as a data source for the texture.
	 * @param {number} [mapping=Texture.DEFAULT_MAPPING] - The texture mapping.
	 * @param {number} [wrapS=ClampToEdgeWrapping] - The wrapS value.
	 * @param {number} [wrapT=ClampToEdgeWrapping] - The wrapT value.
	 * @param {number} [magFilter=LinearFilter] - The mag filter value.
	 * @param {number} [minFilter=LinearFilter] - The min filter value.
	 * @param {number} [format=RGBAFormat] - The texture format.
	 * @param {number} [type=UnsignedByteType] - The texture type.
	 * @param {number} [anisotropy=Texture.DEFAULT_ANISOTROPY] - The anisotropy value.
	 */
	constructor( video, mapping, wrapS, wrapT, magFilter = _constants_js__WEBPACK_IMPORTED_MODULE_1__/* .LinearFilter */ .k6q, minFilter = _constants_js__WEBPACK_IMPORTED_MODULE_1__/* .LinearFilter */ .k6q, format, type, anisotropy ) {

		super( video, mapping, wrapS, wrapT, magFilter, minFilter, format, type, anisotropy );

		/**
		 * This flag can be used for type testing.
		 *
		 * @type {boolean}
		 * @readonly
		 * @default true
		 */
		this.isVideoTexture = true;

		/**
		 * Whether to generate mipmaps (if possible) for a texture.
		 *
		 * Overwritten and set to `false` by default.
		 *
		 * @type {boolean}
		 * @default false
		 */
		this.generateMipmaps = false;

		/**
		 * The video frame request callback identifier, which is a positive integer.
		 *
		 * Value of 0 represents no scheduled rVFC.
		 *
		 * @private
		 * @type {number}
		 */
		this._requestVideoFrameCallbackId = 0;

		const scope = this;

		function updateVideo() {

			scope.needsUpdate = true;
			scope._requestVideoFrameCallbackId = video.requestVideoFrameCallback( updateVideo );

		}

		if ( 'requestVideoFrameCallback' in video ) {

			this._requestVideoFrameCallbackId = video.requestVideoFrameCallback( updateVideo );

		}

	}

	clone() {

		return new this.constructor( this.image ).copy( this );

	}

	/**
	 * This method is called automatically by the renderer and sets {@link Texture#needsUpdate}
	 * to `true` every time a new frame is available.
	 *
	 * Only relevant if `requestVideoFrameCallback` is not supported in the browser.
	 */
	update() {

		const video = this.image;
		const hasVideoFrameCallback = 'requestVideoFrameCallback' in video;

		if ( hasVideoFrameCallback === false && video.readyState >= video.HAVE_CURRENT_DATA ) {

			this.needsUpdate = true;

		}

	}

	dispose() {

		if ( this._requestVideoFrameCallbackId !== 0 ) {

			this.source.data.cancelVideoFrameCallback( this._requestVideoFrameCallbackId );

			this._requestVideoFrameCallbackId = 0;

		}

		super.dispose();

	}

}




/***/ })

}]);
//# sourceMappingURL=702.bundle.js.map