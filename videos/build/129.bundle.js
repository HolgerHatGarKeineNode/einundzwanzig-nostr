"use strict";
(self["webpackChunkvideos"] = self["webpackChunkvideos"] || []).push([[129],{

/***/ 9129:
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  TextureLoader: () => (/* binding */ TextureLoader)
});

;// ./node_modules/three/src/loaders/Cache.js
/**
 * @class
 * @classdesc A simple caching system, used internally by {@link FileLoader}.
 * To enable caching across all loaders that use {@link FileLoader}, add `THREE.Cache.enabled = true.` once in your app.
 * @hideconstructor
 */
const Cache = {

	/**
	 * Whether caching is enabled or not.
	 *
	 * @static
	 * @type {boolean}
	 * @default false
	 */
	enabled: false,

	/**
	 * A dictionary that holds cached files.
	 *
	 * @static
	 * @type {Object<string,Object>}
	 */
	files: {},

	/**
	 * Adds a cache entry with a key to reference the file. If this key already
	 * holds a file, it is overwritten.
	 *
	 * @static
	 * @param {string} key - The key to reference the cached file.
	 * @param {Object} file -  The file to be cached.
	 */
	add: function ( key, file ) {

		if ( this.enabled === false ) return;

		// log( 'Cache', 'Adding key:', key );

		this.files[ key ] = file;

	},

	/**
	 * Gets the cached value for the given key.
	 *
	 * @static
	 * @param {string} key - The key to reference the cached file.
	 * @return {Object|undefined} The cached file. If the key does not exist `undefined` is returned.
	 */
	get: function ( key ) {

		if ( this.enabled === false ) return;

		// log( 'Cache', 'Checking key:', key );

		return this.files[ key ];

	},

	/**
	 * Removes the cached file associated with the given key.
	 *
	 * @static
	 * @param {string} key - The key to reference the cached file.
	 */
	remove: function ( key ) {

		delete this.files[ key ];

	},

	/**
	 * Remove all values from the cache.
	 *
	 * @static
	 */
	clear: function () {

		this.files = {};

	}

};




;// ./node_modules/three/src/loaders/LoadingManager.js
/**
 * Handles and keeps track of loaded and pending data. A default global
 * instance of this class is created and used by loaders if not supplied
 * manually.
 *
 * In general that should be sufficient, however there are times when it can
 * be useful to have separate loaders - for example if you want to show
 * separate loading bars for objects and textures.
 *
 * ```js
 * const manager = new THREE.LoadingManager();
 * manager.onLoad = () => console.log( 'Loading complete!' );
 *
 * const loader1 = new OBJLoader( manager );
 * const loader2 = new ColladaLoader( manager );
 * ```
 */
class LoadingManager {

	/**
	 * Constructs a new loading manager.
	 *
	 * @param {Function} [onLoad] - Executes when all items have been loaded.
	 * @param {Function} [onProgress] - Executes when single items have been loaded.
	 * @param {Function} [onError] - Executes when an error occurs.
	 */
	constructor( onLoad, onProgress, onError ) {

		const scope = this;

		let isLoading = false;
		let itemsLoaded = 0;
		let itemsTotal = 0;
		let urlModifier = undefined;
		const handlers = [];

		// Refer to #5689 for the reason why we don't set .onStart
		// in the constructor

		/**
		 * Executes when an item starts loading.
		 *
		 * @type {Function|undefined}
		 * @default undefined
		 */
		this.onStart = undefined;

		/**
		 * Executes when all items have been loaded.
		 *
		 * @type {Function|undefined}
		 * @default undefined
		 */
		this.onLoad = onLoad;

		/**
		 * Executes when single items have been loaded.
		 *
		 * @type {Function|undefined}
		 * @default undefined
		 */
		this.onProgress = onProgress;

		/**
		 * Executes when an error occurs.
		 *
		 * @type {Function|undefined}
		 * @default undefined
		 */
		this.onError = onError;

		/**
		 * Used for aborting ongoing requests in loaders using this manager.
		 *
		 * @private
		 * @type {AbortController | null}
		 */
		this._abortController = null;

		/**
		 * This should be called by any loader using the manager when the loader
		 * starts loading an item.
		 *
		 * @param {string} url - The URL to load.
		 */
		this.itemStart = function ( url ) {

			itemsTotal ++;

			if ( isLoading === false ) {

				if ( scope.onStart !== undefined ) {

					scope.onStart( url, itemsLoaded, itemsTotal );

				}

			}

			isLoading = true;

		};

		/**
		 * This should be called by any loader using the manager when the loader
		 * ended loading an item.
		 *
		 * @param {string} url - The URL of the loaded item.
		 */
		this.itemEnd = function ( url ) {

			itemsLoaded ++;

			if ( scope.onProgress !== undefined ) {

				scope.onProgress( url, itemsLoaded, itemsTotal );

			}

			if ( itemsLoaded === itemsTotal ) {

				isLoading = false;

				if ( scope.onLoad !== undefined ) {

					scope.onLoad();

				}

			}

		};

		/**
		 * This should be called by any loader using the manager when the loader
		 * encounters an error when loading an item.
		 *
		 * @param {string} url - The URL of the item that produces an error.
		 */
		this.itemError = function ( url ) {

			if ( scope.onError !== undefined ) {

				scope.onError( url );

			}

		};

		/**
		 * Given a URL, uses the URL modifier callback (if any) and returns a
		 * resolved URL. If no URL modifier is set, returns the original URL.
		 *
		 * @param {string} url - The URL to load.
		 * @return {string} The resolved URL.
		 */
		this.resolveURL = function ( url ) {

			if ( urlModifier ) {

				return urlModifier( url );

			}

			return url;

		};

		/**
		 * If provided, the callback will be passed each resource URL before a
		 * request is sent. The callback may return the original URL, or a new URL to
		 * override loading behavior. This behavior can be used to load assets from
		 * .ZIP files, drag-and-drop APIs, and Data URIs.
		 *
		 * ```js
		 * const blobs = {'fish.gltf': blob1, 'diffuse.png': blob2, 'normal.png': blob3};
		 *
		 * const manager = new THREE.LoadingManager();
		 *
		 * // Initialize loading manager with URL callback.
		 * const objectURLs = [];
		 * manager.setURLModifier( ( url ) => {
		 *
		 * 	url = URL.createObjectURL( blobs[ url ] );
		 * 	objectURLs.push( url );
		 * 	return url;
		 *
		 * } );
		 *
		 * // Load as usual, then revoke the blob URLs.
		 * const loader = new GLTFLoader( manager );
		 * loader.load( 'fish.gltf', (gltf) => {
		 *
		 * 	scene.add( gltf.scene );
		 * 	objectURLs.forEach( ( url ) => URL.revokeObjectURL( url ) );
		 *
		 * } );
		 * ```
		 *
		 * @param {function(string):string} transform - URL modifier callback. Called with an URL and must return a resolved URL.
		 * @return {LoadingManager} A reference to this loading manager.
		 */
		this.setURLModifier = function ( transform ) {

			urlModifier = transform;

			return this;

		};

		/**
		 * Registers a loader with the given regular expression. Can be used to
		 * define what loader should be used in order to load specific files. A
		 * typical use case is to overwrite the default loader for textures.
		 *
		 * ```js
		 * // add handler for TGA textures
		 * manager.addHandler( /\.tga$/i, new TGALoader() );
		 * ```
		 *
		 * @param {string} regex - A regular expression.
		 * @param {Loader} loader - A loader that should handle matched cases.
		 * @return {LoadingManager} A reference to this loading manager.
		 */
		this.addHandler = function ( regex, loader ) {

			handlers.push( regex, loader );

			return this;

		};

		/**
		 * Removes the loader for the given regular expression.
		 *
		 * @param {string} regex - A regular expression.
		 * @return {LoadingManager} A reference to this loading manager.
		 */
		this.removeHandler = function ( regex ) {

			const index = handlers.indexOf( regex );

			if ( index !== - 1 ) {

				handlers.splice( index, 2 );

			}

			return this;

		};

		/**
		 * Can be used to retrieve the registered loader for the given file path.
		 *
		 * @param {string} file - The file path.
		 * @return {?Loader} The registered loader. Returns `null` if no loader was found.
		 */
		this.getHandler = function ( file ) {

			for ( let i = 0, l = handlers.length; i < l; i += 2 ) {

				const regex = handlers[ i ];
				const loader = handlers[ i + 1 ];

				if ( regex.global ) regex.lastIndex = 0; // see #17920

				if ( regex.test( file ) ) {

					return loader;

				}

			}

			return null;

		};

		/**
		 * Can be used to abort ongoing loading requests in loaders using this manager.
		 * The abort only works if the loaders implement {@link Loader#abort} and `AbortSignal.any()`
		 * is supported in the browser.
		 *
		 * @return {LoadingManager} A reference to this loading manager.
		 */
		this.abort = function () {


			this.abortController.abort();
			this._abortController = null;

			return this;

		};

	}

	// TODO: Revert this back to a single member variable once this issue has been fixed
	// https://github.com/cloudflare/workerd/issues/3657

	/**
	 * Used for aborting ongoing requests in loaders using this manager.
	 *
	 * @type {AbortController}
	 */
	get abortController() {

		if ( ! this._abortController ) {

			this._abortController = new AbortController();

		}

		return this._abortController;

	}

}

/**
 * The global default loading manager.
 *
 * @constant
 * @type {LoadingManager}
 */
const DefaultLoadingManager = /*@__PURE__*/ new LoadingManager();



;// ./node_modules/three/src/loaders/Loader.js


/**
 * Abstract base class for loaders.
 *
 * @abstract
 */
class Loader {

	/**
	 * Constructs a new loader.
	 *
	 * @param {LoadingManager} [manager] - The loading manager.
	 */
	constructor( manager ) {

		/**
		 * The loading manager.
		 *
		 * @type {LoadingManager}
		 * @default DefaultLoadingManager
		 */
		this.manager = ( manager !== undefined ) ? manager : DefaultLoadingManager;

		/**
		 * The crossOrigin string to implement CORS for loading the url from a
		 * different domain that allows CORS.
		 *
		 * @type {string}
		 * @default 'anonymous'
		 */
		this.crossOrigin = 'anonymous';

		/**
		 * Whether the XMLHttpRequest uses credentials.
		 *
		 * @type {boolean}
		 * @default false
		 */
		this.withCredentials = false;

		/**
		 * The base path from which the asset will be loaded.
		 *
		 * @type {string}
		 */
		this.path = '';

		/**
		 * The base path from which additional resources like textures will be loaded.
		 *
		 * @type {string}
		 */
		this.resourcePath = '';

		/**
		 * The [request header](https://developer.mozilla.org/en-US/docs/Glossary/Request_header)
		 * used in HTTP request.
		 *
		 * @type {Object<string, any>}
		 */
		this.requestHeader = {};

	}

	/**
	 * This method needs to be implemented by all concrete loaders. It holds the
	 * logic for loading assets from the backend.
	 *
	 * @abstract
	 * @param {string} url - The path/URL of the file to be loaded.
	 * @param {Function} onLoad - Executed when the loading process has been finished.
	 * @param {onProgressCallback} [onProgress] - Executed while the loading is in progress.
	 * @param {onErrorCallback} [onError] - Executed when errors occur.
	 */
	load( /* url, onLoad, onProgress, onError */ ) {}

	/**
	 * A async version of {@link Loader#load}.
	 *
	 * @param {string} url - The path/URL of the file to be loaded.
	 * @param {onProgressCallback} [onProgress] - Executed while the loading is in progress.
	 * @return {Promise} A Promise that resolves when the asset has been loaded.
	 */
	loadAsync( url, onProgress ) {

		const scope = this;

		return new Promise( function ( resolve, reject ) {

			scope.load( url, resolve, onProgress, reject );

		} );

	}

	/**
	 * This method needs to be implemented by all concrete loaders. It holds the
	 * logic for parsing the asset into three.js entities.
	 *
	 * @abstract
	 * @param {any} data - The data to parse.
	 */
	parse( /* data */ ) {}

	/**
	 * Sets the `crossOrigin` String to implement CORS for loading the URL
	 * from a different domain that allows CORS.
	 *
	 * @param {string} crossOrigin - The `crossOrigin` value.
	 * @return {Loader} A reference to this instance.
	 */
	setCrossOrigin( crossOrigin ) {

		this.crossOrigin = crossOrigin;
		return this;

	}

	/**
	 * Whether the XMLHttpRequest uses credentials such as cookies, authorization
	 * headers or TLS client certificates, see [XMLHttpRequest.withCredentials](https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/withCredentials).
	 *
	 * Note: This setting has no effect if you are loading files locally or from the same domain.
	 *
	 * @param {boolean} value - The `withCredentials` value.
	 * @return {Loader} A reference to this instance.
	 */
	setWithCredentials( value ) {

		this.withCredentials = value;
		return this;

	}

	/**
	 * Sets the base path for the asset.
	 *
	 * @param {string} path - The base path.
	 * @return {Loader} A reference to this instance.
	 */
	setPath( path ) {

		this.path = path;
		return this;

	}

	/**
	 * Sets the base path for dependent resources like textures.
	 *
	 * @param {string} resourcePath - The resource path.
	 * @return {Loader} A reference to this instance.
	 */
	setResourcePath( resourcePath ) {

		this.resourcePath = resourcePath;
		return this;

	}

	/**
	 * Sets the given request header.
	 *
	 * @param {Object} requestHeader - A [request header](https://developer.mozilla.org/en-US/docs/Glossary/Request_header)
	 * for configuring the HTTP request.
	 * @return {Loader} A reference to this instance.
	 */
	setRequestHeader( requestHeader ) {

		this.requestHeader = requestHeader;
		return this;

	}

	/**
	 * This method can be implemented in loaders for aborting ongoing requests.
	 *
	 * @abstract
	 * @return {Loader} A reference to this instance.
	 */
	abort() {

		return this;

	}

}

/**
 * Callback for onProgress in loaders.
 *
 * @callback onProgressCallback
 * @param {ProgressEvent} event - An instance of `ProgressEvent` that represents the current loading status.
 */

/**
 * Callback for onError in loaders.
 *
 * @callback onErrorCallback
 * @param {Error} error - The error which occurred during the loading process.
 */

/**
 * The default material name that is used by loaders
 * when creating materials for loaded 3D objects.
 *
 * Note: Not all loaders might honor this setting.
 *
 * @static
 * @type {string}
 * @default '__DEFAULT'
 */
Loader.DEFAULT_MATERIAL_NAME = '__DEFAULT';



// EXTERNAL MODULE: ./node_modules/three/src/utils.js
var utils = __webpack_require__(8108);
;// ./node_modules/three/src/loaders/ImageLoader.js




const _loading = new WeakMap();

/**
 * A loader for loading images. The class loads images with the HTML `Image` API.
 *
 * ```js
 * const loader = new THREE.ImageLoader();
 * const image = await loader.loadAsync( 'image.png' );
 * ```
 * Please note that `ImageLoader` has dropped support for progress
 * events in `r84`. For an `ImageLoader` that supports progress events, see
 * [this thread](https://github.com/mrdoob/three.js/issues/10439#issuecomment-275785639).
 *
 * @augments Loader
 */
class ImageLoader extends Loader {

	/**
	 * Constructs a new image loader.
	 *
	 * @param {LoadingManager} [manager] - The loading manager.
	 */
	constructor( manager ) {

		super( manager );

	}

	/**
	 * Starts loading from the given URL and passes the loaded image
	 * to the `onLoad()` callback. The method also returns a new `Image` object which can
	 * directly be used for texture creation. If you do it this way, the texture
	 * may pop up in your scene once the respective loading process is finished.
	 *
	 * @param {string} url - The path/URL of the file to be loaded. This can also be a data URI.
	 * @param {function(Image)} onLoad - Executed when the loading process has been finished.
	 * @param {onProgressCallback} onProgress - Unsupported in this loader.
	 * @param {onErrorCallback} onError - Executed when errors occur.
	 * @return {Image} The image.
	 */
	load( url, onLoad, onProgress, onError ) {

		if ( this.path !== undefined ) url = this.path + url;

		url = this.manager.resolveURL( url );

		const scope = this;

		const cached = Cache.get( `image:${url}` );

		if ( cached !== undefined ) {

			if ( cached.complete === true ) {

				scope.manager.itemStart( url );

				setTimeout( function () {

					if ( onLoad ) onLoad( cached );

					scope.manager.itemEnd( url );

				}, 0 );

			} else {

				let arr = _loading.get( cached );

				if ( arr === undefined ) {

					arr = [];
					_loading.set( cached, arr );

				}

				arr.push( { onLoad, onError } );

			}

			return cached;

		}

		const image = (0,utils/* createElementNS */.qq)( 'img' );

		function onImageLoad() {

			removeEventListeners();

			if ( onLoad ) onLoad( this );

			//

			const callbacks = _loading.get( this ) || [];

			for ( let i = 0; i < callbacks.length; i ++ ) {

				const callback = callbacks[ i ];
				if ( callback.onLoad ) callback.onLoad( this );

			}

			_loading.delete( this );

			scope.manager.itemEnd( url );

		}

		function onImageError( event ) {

			removeEventListeners();

			if ( onError ) onError( event );

			Cache.remove( `image:${url}` );

			//

			const callbacks = _loading.get( this ) || [];

			for ( let i = 0; i < callbacks.length; i ++ ) {

				const callback = callbacks[ i ];
				if ( callback.onError ) callback.onError( event );

			}

			_loading.delete( this );


			scope.manager.itemError( url );
			scope.manager.itemEnd( url );

		}

		function removeEventListeners() {

			image.removeEventListener( 'load', onImageLoad, false );
			image.removeEventListener( 'error', onImageError, false );

		}

		image.addEventListener( 'load', onImageLoad, false );
		image.addEventListener( 'error', onImageError, false );

		if ( url.slice( 0, 5 ) !== 'data:' ) {

			if ( this.crossOrigin !== undefined ) image.crossOrigin = this.crossOrigin;

		}

		Cache.add( `image:${url}`, image );
		scope.manager.itemStart( url );

		image.src = url;

		return image;

	}

}




// EXTERNAL MODULE: ./node_modules/three/src/textures/Texture.js + 9 modules
var Texture = __webpack_require__(8280);
;// ./node_modules/three/src/loaders/TextureLoader.js




/**
 * Class for loading textures. Images are internally
 * loaded via {@link ImageLoader}.
 *
 * ```js
 * const loader = new THREE.TextureLoader();
 * const texture = await loader.loadAsync( 'textures/land_ocean_ice_cloud_2048.jpg' );
 *
 * const material = new THREE.MeshBasicMaterial( { map:texture } );
 * ```
 * Please note that `TextureLoader` has dropped support for progress
 * events in `r84`. For a `TextureLoader` that supports progress events, see
 * [this thread](https://github.com/mrdoob/three.js/issues/10439#issuecomment-293260145).
 *
 * @augments Loader
 */
class TextureLoader extends Loader {

	/**
	 * Constructs a new texture loader.
	 *
	 * @param {LoadingManager} [manager] - The loading manager.
	 */
	constructor( manager ) {

		super( manager );

	}

	/**
	 * Starts loading from the given URL and pass the fully loaded texture
	 * to the `onLoad()` callback. The method also returns a new texture object which can
	 * directly be used for material creation. If you do it this way, the texture
	 * may pop up in your scene once the respective loading process is finished.
	 *
	 * @param {string} url - The path/URL of the file to be loaded. This can also be a data URI.
	 * @param {function(Texture)} onLoad - Executed when the loading process has been finished.
	 * @param {onProgressCallback} onProgress - Unsupported in this loader.
	 * @param {onErrorCallback} onError - Executed when errors occur.
	 * @return {Texture} The texture.
	 */
	load( url, onLoad, onProgress, onError ) {

		const texture = new Texture/* Texture */.g();

		const loader = new ImageLoader( this.manager );
		loader.setCrossOrigin( this.crossOrigin );
		loader.setPath( this.path );

		loader.load( url, function ( image ) {

			texture.image = image;
			texture.needsUpdate = true;

			if ( onLoad !== undefined ) {

				onLoad( texture );

			}

		}, onProgress, onError );

		return texture;

	}

}





/***/ })

}]);
//# sourceMappingURL=129.bundle.js.map