/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/credit-card-type/dist/index.js":
/*!*****************************************************!*\
  !*** ./node_modules/credit-card-type/dist/index.js ***!
  \*****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {


var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var cardTypes = __webpack_require__(/*! ./lib/card-types */ "./node_modules/credit-card-type/dist/lib/card-types.js");
var add_matching_cards_to_results_1 = __webpack_require__(/*! ./lib/add-matching-cards-to-results */ "./node_modules/credit-card-type/dist/lib/add-matching-cards-to-results.js");
var is_valid_input_type_1 = __webpack_require__(/*! ./lib/is-valid-input-type */ "./node_modules/credit-card-type/dist/lib/is-valid-input-type.js");
var find_best_match_1 = __webpack_require__(/*! ./lib/find-best-match */ "./node_modules/credit-card-type/dist/lib/find-best-match.js");
var clone_1 = __webpack_require__(/*! ./lib/clone */ "./node_modules/credit-card-type/dist/lib/clone.js");
var customCards = {};
var cardNames = {
    VISA: "visa",
    MASTERCARD: "mastercard",
    AMERICAN_EXPRESS: "american-express",
    DINERS_CLUB: "diners-club",
    DISCOVER: "discover",
    JCB: "jcb",
    UNIONPAY: "unionpay",
    MAESTRO: "maestro",
    ELO: "elo",
    MIR: "mir",
    HIPER: "hiper",
    HIPERCARD: "hipercard",
};
var ORIGINAL_TEST_ORDER = [
    cardNames.VISA,
    cardNames.MASTERCARD,
    cardNames.AMERICAN_EXPRESS,
    cardNames.DINERS_CLUB,
    cardNames.DISCOVER,
    cardNames.JCB,
    cardNames.UNIONPAY,
    cardNames.MAESTRO,
    cardNames.ELO,
    cardNames.MIR,
    cardNames.HIPER,
    cardNames.HIPERCARD,
];
var testOrder = (0, clone_1.clone)(ORIGINAL_TEST_ORDER);
function findType(cardType) {
    return customCards[cardType] || cardTypes[cardType];
}
function getAllCardTypes() {
    return testOrder.map(function (cardType) { return (0, clone_1.clone)(findType(cardType)); });
}
function getCardPosition(name, ignoreErrorForNotExisting) {
    if (ignoreErrorForNotExisting === void 0) { ignoreErrorForNotExisting = false; }
    var position = testOrder.indexOf(name);
    if (!ignoreErrorForNotExisting && position === -1) {
        throw new Error('"' + name + '" is not a supported card type.');
    }
    return position;
}
function creditCardType(cardNumber) {
    var results = [];
    if (!(0, is_valid_input_type_1.isValidInputType)(cardNumber)) {
        return results;
    }
    if (cardNumber.length === 0) {
        return getAllCardTypes();
    }
    testOrder.forEach(function (cardType) {
        var cardConfiguration = findType(cardType);
        (0, add_matching_cards_to_results_1.addMatchingCardsToResults)(cardNumber, cardConfiguration, results);
    });
    var bestMatch = (0, find_best_match_1.findBestMatch)(results);
    if (bestMatch) {
        return [bestMatch];
    }
    return results;
}
creditCardType.getTypeInfo = function (cardType) {
    return (0, clone_1.clone)(findType(cardType));
};
creditCardType.removeCard = function (name) {
    var position = getCardPosition(name);
    testOrder.splice(position, 1);
};
creditCardType.addCard = function (config) {
    var existingCardPosition = getCardPosition(config.type, true);
    customCards[config.type] = config;
    if (existingCardPosition === -1) {
        testOrder.push(config.type);
    }
};
creditCardType.updateCard = function (cardType, updates) {
    var originalObject = customCards[cardType] || cardTypes[cardType];
    if (!originalObject) {
        throw new Error("\"".concat(cardType, "\" is not a recognized type. Use `addCard` instead.'"));
    }
    if (updates.type && originalObject.type !== updates.type) {
        throw new Error("Cannot overwrite type parameter.");
    }
    var clonedCard = (0, clone_1.clone)(originalObject);
    clonedCard = __assign(__assign({}, clonedCard), updates);
    customCards[clonedCard.type] = clonedCard;
};
creditCardType.changeOrder = function (name, position) {
    var currentPosition = getCardPosition(name);
    testOrder.splice(currentPosition, 1);
    testOrder.splice(position, 0, name);
};
creditCardType.resetModifications = function () {
    testOrder = (0, clone_1.clone)(ORIGINAL_TEST_ORDER);
    customCards = {};
};
creditCardType.types = cardNames;
module.exports = creditCardType;


/***/ }),

/***/ "./node_modules/credit-card-type/dist/lib/add-matching-cards-to-results.js":
/*!*********************************************************************************!*\
  !*** ./node_modules/credit-card-type/dist/lib/add-matching-cards-to-results.js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.addMatchingCardsToResults = void 0;
var clone_1 = __webpack_require__(/*! ./clone */ "./node_modules/credit-card-type/dist/lib/clone.js");
var matches_1 = __webpack_require__(/*! ./matches */ "./node_modules/credit-card-type/dist/lib/matches.js");
function addMatchingCardsToResults(cardNumber, cardConfiguration, results) {
    var i, patternLength;
    for (i = 0; i < cardConfiguration.patterns.length; i++) {
        var pattern = cardConfiguration.patterns[i];
        if (!(0, matches_1.matches)(cardNumber, pattern)) {
            continue;
        }
        var clonedCardConfiguration = (0, clone_1.clone)(cardConfiguration);
        if (Array.isArray(pattern)) {
            patternLength = String(pattern[0]).length;
        }
        else {
            patternLength = String(pattern).length;
        }
        if (cardNumber.length >= patternLength) {
            clonedCardConfiguration.matchStrength = patternLength;
        }
        results.push(clonedCardConfiguration);
        break;
    }
}
exports.addMatchingCardsToResults = addMatchingCardsToResults;


/***/ }),

/***/ "./node_modules/credit-card-type/dist/lib/card-types.js":
/*!**************************************************************!*\
  !*** ./node_modules/credit-card-type/dist/lib/card-types.js ***!
  \**************************************************************/
/***/ ((module) => {


var cardTypes = {
    visa: {
        niceType: "Visa",
        type: "visa",
        patterns: [4],
        gaps: [4, 8, 12],
        lengths: [16, 18, 19],
        code: {
            name: "CVV",
            size: 3,
        },
    },
    mastercard: {
        niceType: "Mastercard",
        type: "mastercard",
        patterns: [[51, 55], [2221, 2229], [223, 229], [23, 26], [270, 271], 2720],
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
            name: "CVC",
            size: 3,
        },
    },
    "american-express": {
        niceType: "American Express",
        type: "american-express",
        patterns: [34, 37],
        gaps: [4, 10],
        lengths: [15],
        code: {
            name: "CID",
            size: 4,
        },
    },
    "diners-club": {
        niceType: "Diners Club",
        type: "diners-club",
        patterns: [[300, 305], 36, 38, 39],
        gaps: [4, 10],
        lengths: [14, 16, 19],
        code: {
            name: "CVV",
            size: 3,
        },
    },
    discover: {
        niceType: "Discover",
        type: "discover",
        patterns: [6011, [644, 649], 65],
        gaps: [4, 8, 12],
        lengths: [16, 19],
        code: {
            name: "CID",
            size: 3,
        },
    },
    jcb: {
        niceType: "JCB",
        type: "jcb",
        patterns: [2131, 1800, [3528, 3589]],
        gaps: [4, 8, 12],
        lengths: [16, 17, 18, 19],
        code: {
            name: "CVV",
            size: 3,
        },
    },
    unionpay: {
        niceType: "UnionPay",
        type: "unionpay",
        patterns: [
            620,
            [62100, 62182],
            [62184, 62187],
            [62185, 62197],
            [62200, 62205],
            [622010, 622999],
            622018,
            [62207, 62209],
            [623, 626],
            6270,
            6272,
            6276,
            [627700, 627779],
            [627781, 627799],
            [6282, 6289],
            6291,
            6292,
            810,
            [8110, 8131],
            [8132, 8151],
            [8152, 8163],
            [8164, 8171],
        ],
        gaps: [4, 8, 12],
        lengths: [14, 15, 16, 17, 18, 19],
        code: {
            name: "CVN",
            size: 3,
        },
    },
    maestro: {
        niceType: "Maestro",
        type: "maestro",
        patterns: [
            493698,
            [500000, 504174],
            [504176, 506698],
            [506779, 508999],
            [56, 59],
            63,
            67,
            6,
        ],
        gaps: [4, 8, 12],
        lengths: [12, 13, 14, 15, 16, 17, 18, 19],
        code: {
            name: "CVC",
            size: 3,
        },
    },
    elo: {
        niceType: "Elo",
        type: "elo",
        patterns: [
            401178,
            401179,
            438935,
            457631,
            457632,
            431274,
            451416,
            457393,
            504175,
            [506699, 506778],
            [509000, 509999],
            627780,
            636297,
            636368,
            [650031, 650033],
            [650035, 650051],
            [650405, 650439],
            [650485, 650538],
            [650541, 650598],
            [650700, 650718],
            [650720, 650727],
            [650901, 650978],
            [651652, 651679],
            [655000, 655019],
            [655021, 655058],
        ],
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
            name: "CVE",
            size: 3,
        },
    },
    mir: {
        niceType: "Mir",
        type: "mir",
        patterns: [[2200, 2204]],
        gaps: [4, 8, 12],
        lengths: [16, 17, 18, 19],
        code: {
            name: "CVP2",
            size: 3,
        },
    },
    hiper: {
        niceType: "Hiper",
        type: "hiper",
        patterns: [637095, 63737423, 63743358, 637568, 637599, 637609, 637612],
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
            name: "CVC",
            size: 3,
        },
    },
    hipercard: {
        niceType: "Hipercard",
        type: "hipercard",
        patterns: [606282],
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
            name: "CVC",
            size: 3,
        },
    },
};
module.exports = cardTypes;


/***/ }),

/***/ "./node_modules/credit-card-type/dist/lib/clone.js":
/*!*********************************************************!*\
  !*** ./node_modules/credit-card-type/dist/lib/clone.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, exports) => {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.clone = void 0;
function clone(originalObject) {
    if (!originalObject) {
        return null;
    }
    return JSON.parse(JSON.stringify(originalObject));
}
exports.clone = clone;


/***/ }),

/***/ "./node_modules/credit-card-type/dist/lib/find-best-match.js":
/*!*******************************************************************!*\
  !*** ./node_modules/credit-card-type/dist/lib/find-best-match.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, exports) => {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.findBestMatch = void 0;
function hasEnoughResultsToDetermineBestMatch(results) {
    var numberOfResultsWithMaxStrengthProperty = results.filter(function (result) { return result.matchStrength; }).length;
    /*
     * if all possible results have a maxStrength property that means the card
     * number is sufficiently long enough to determine conclusively what the card
     * type is
     * */
    return (numberOfResultsWithMaxStrengthProperty > 0 &&
        numberOfResultsWithMaxStrengthProperty === results.length);
}
function findBestMatch(results) {
    if (!hasEnoughResultsToDetermineBestMatch(results)) {
        return null;
    }
    return results.reduce(function (bestMatch, result) {
        if (!bestMatch) {
            return result;
        }
        /*
         * If the current best match pattern is less specific than this result, set
         * the result as the new best match
         * */
        if (Number(bestMatch.matchStrength) < Number(result.matchStrength)) {
            return result;
        }
        return bestMatch;
    });
}
exports.findBestMatch = findBestMatch;


/***/ }),

/***/ "./node_modules/credit-card-type/dist/lib/is-valid-input-type.js":
/*!***********************************************************************!*\
  !*** ./node_modules/credit-card-type/dist/lib/is-valid-input-type.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, exports) => {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.isValidInputType = void 0;
function isValidInputType(cardNumber) {
    return typeof cardNumber === "string" || cardNumber instanceof String;
}
exports.isValidInputType = isValidInputType;


/***/ }),

/***/ "./node_modules/credit-card-type/dist/lib/matches.js":
/*!***********************************************************!*\
  !*** ./node_modules/credit-card-type/dist/lib/matches.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, exports) => {


/*
 * Adapted from https://github.com/polvo-labs/card-type/blob/aaab11f80fa1939bccc8f24905a06ae3cd864356/src/cardType.js#L37-L42
 * */
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.matches = void 0;
function matchesRange(cardNumber, min, max) {
    var maxLengthToCheck = String(min).length;
    var substr = cardNumber.substr(0, maxLengthToCheck);
    var integerRepresentationOfCardNumber = parseInt(substr, 10);
    min = parseInt(String(min).substr(0, substr.length), 10);
    max = parseInt(String(max).substr(0, substr.length), 10);
    return (integerRepresentationOfCardNumber >= min &&
        integerRepresentationOfCardNumber <= max);
}
function matchesPattern(cardNumber, pattern) {
    pattern = String(pattern);
    return (pattern.substring(0, cardNumber.length) ===
        cardNumber.substring(0, pattern.length));
}
function matches(cardNumber, pattern) {
    if (Array.isArray(pattern)) {
        return matchesRange(cardNumber, pattern[0], pattern[1]);
    }
    return matchesPattern(cardNumber, pattern);
}
exports.matches = matches;


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ ((module) => {

module.exports = window["wc"]["blocksCheckout"];

/***/ }),

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!*****************************************************!*\
  !*** ./assets/js/payment-method-borgun-rpg/view.js ***!
  \*****************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-registry */ "@woocommerce/blocks-registry");
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var credit_card_type__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! credit-card-type */ "./node_modules/credit-card-type/dist/index.js");
/* harmony import */ var credit_card_type__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(credit_card_type__WEBPACK_IMPORTED_MODULE_7__);
var _settings$supports;








const settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_5__.getPaymentMethodData)('borgun_rpg', {});
const defaultLabel = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Teya RPG', 'borgun_rpg');
const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings?.title || '') || defaultLabel;
const publickey = settings?.publickey || '';
const BorgunRPGDesc = () => {
  return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.description || '');
};
const BorgunRPGTestCardNotice = () => {
  if (settings.testmode) {
    const testModeText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('In test mode, you can use the card number 4741520000000003 with any CVC and a valid expiration date', 'borgun_rpg');
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      class: "borgun-rpg-testmode-notice"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("i", null, testModeText));
  }
};

/**
 * Content component
 */
const Content = props => {
  const [cardValue, setCardValue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)('');
  const [cardExpiryValue, setCardExpiryValue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)('');
  const [cardCodeValue, setCardCodeValue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)('');
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onPaymentSetup
  } = eventRegistration;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    const unsubscribe = onPaymentSetup(async () => {
      if (publickey) {
        BAPIjs.setPublicToken(publickey);
        if (BAPIjs.isValidCardNumber(cardValue) === false) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Invalid card number', 'borgun_rpg')
          };
        }
        var expiry = cardExpiryValue.split('/');
        var expMonth = '00';
        if (0 in expiry) {
          expMonth = expiry[0].replace(/\s+/g, '');
        }
        var expYear = '00';
        if (1 in expiry) {
          expYear = expiry[1].replace(/\s+/g, '');
        }
        if (BAPIjs.isValidExpDate(expMonth, expYear) === false) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Invalid expiration date', 'borgun_rpg')
          };
        }
        if (BAPIjs.isValidCVC(cardCodeValue) === false) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Invalid cvc number', 'borgun_rpg')
          };
        }
        let errorMessage = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Not authorized, token failed', 'borgun_rpg');
        let bapiToken = '';
        const promise = new Promise((resolve, reject) => {
          const BorgunResponseHandler = function (status, data) {
            if (status.statusCode === 201) {
              bapiToken = data.Token;
            } else if (status.statusCode === 401) {
              errorMessage = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Unauthorized received from TeyaPaymentAPI', 'borgun_rpg');
            } else if (status.statusCode) {
              errorMessage = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Error received from TeyaPaymentAPI', 'borgun_rpg') + ' ' + status.statusCode + ' - ' + status.message;
            } else {
              errorMessage = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Unable to connect to server', 'borgun_rpg');
            }
            resolve([bapiToken, errorMessage]);
          };
          BAPIjs.getToken({
            'pan': cardValue,
            'expMonth': expMonth,
            'expYear': expYear,
            'cvc': cardCodeValue
          }, BorgunResponseHandler);
        });
        const BAPIResponse = await promise;
        if (!BAPIResponse[0]) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: BAPIResponse[1]
          };
        } else {
          return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                'borgun-rpg-card-token': BAPIResponse[0]
              }
            }
          };
        }
      }
      return {
        type: emitResponse.responseTypes.ERROR,
        message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('There was an error', 'borgun_rpg')
      };
    });

    // Unsubscribes when this component is unmounted.
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup, cardValue, cardExpiryValue, cardCodeValue]);
  return [(0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(BorgunRPGDesc, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(BorgunRPGTestCardNotice, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'borgun-rpg-fields wc-block-card-elements'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'borgun-rpg-payment-field borgun-rpg-payment-card wc-block-gateway-container wc-card-number-element'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_6__.ValidatedTextInput, {
    id: "borgun-rpg-card-number",
    name: "borgun-rpg-card-number",
    type: "tel",
    required: true,
    class: 'borgun-rpg-input borgun-rpg-card-input',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card number', 'borgun_rpg'),
    inputmode: "numeric",
    maxlength: "19",
    autocomplete: "cc-number",
    value: cardValue,
    onChange: nextValue => {
      if (!nextValue) {
        setCardValue('');
      } else if (/^-?\d+$/.test(nextValue)) {
        setCardValue(nextValue);
      } else {
        setCardValue(cardValue !== null && cardValue !== void 0 ? cardValue : '');
      }
    },
    customValidation: (inputObject, cardClass) => {
      if (inputObject.value && parseInt(inputObject.value.length) > 4) {
        const cardType = credit_card_type__WEBPACK_IMPORTED_MODULE_7___default()(inputObject.value);
        if (cardType.length == 1) {
          const lengths = cardType[0]['lengths'];
          if (parseInt(lengths[0]) <= parseInt(inputObject.value.length)) {
            return true;
          }
        }
      }
      inputObject.setCustomValidity((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please enter a valid Card number', 'borgun_rpg'));
      return false;
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'borgun-rpg-payment-field borgun-rpg-payment-field-c-expiry wc-block-gateway-container wc-card-expiry-element'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_6__.ValidatedTextInput, {
    id: "borgun-rpg-card-expiry",
    type: "tel",
    required: true,
    class: 'borgun-rpg-input borgun-rpg-card-expiry-input',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Expiry Date', 'borgun_rpg'),
    value: cardExpiryValue,
    maxlength: "5",
    onChange: nextValue => {
      const parts = nextValue.split('/');
      const length = nextValue.length;
      if (cardExpiryValue.length > length) {
        var _nextValue;
        if (length == 2) {
          nextValue = nextValue.slice(0, -1);
        }
        setCardExpiryValue((_nextValue = nextValue) !== null && _nextValue !== void 0 ? _nextValue : '');
      } else {
        let valid = true;
        parts.forEach((value, index) => {
          if (/^-?\d+$/.test(value)) {
            if (index === 0) {
              if (value <= 12) {
                if (value === '00') {
                  valid = false;
                } else {
                  valid = true;
                }
              } else {
                valid = false;
              }
            } else {
              valid = true;
            }
          } else {
            valid = false;
          }
        });
        if (length == 2) {
          nextValue = nextValue + '/';
        }
        if (valid) {
          var _nextValue2;
          setCardExpiryValue((_nextValue2 = nextValue) !== null && _nextValue2 !== void 0 ? _nextValue2 : '');
        } else {
          setCardExpiryValue(cardExpiryValue !== null && cardExpiryValue !== void 0 ? cardExpiryValue : '');
        }
      }
    },
    customValidation: inputObject => {
      if (inputObject.value && parseInt(inputObject.value.length) > 4) {
        const parts = inputObject.value.split('/');
        const date = new Date();
        const month = date.getMonth();
        const year = date.getFullYear();
        const inputYear = parseInt("20" + parts[1]);
        const inputMonth = parseInt(parts[0]);
        if (inputYear > year && inputYear < year + 20) {
          if (inputMonth > 0 && inputMonth <= 12) {
            return true;
          }
        } else if (inputYear == year) {
          if (inputMonth >= month + 1 && inputMonth <= 12) {
            return true;
          }
        }
      }
      inputObject.setCustomValidity((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please enter a valid Expiry (MM/YY)', 'borgun_rpg'));
      return false;
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'borgun-rpg-payment-field borgun-rpg-payment-field-cvc wc-block-gateway-container wc-card-cvc-element'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_6__.ValidatedTextInput, {
    id: "borgun-rpg-card-cvc",
    type: "tel",
    name: "borgun-rpg-card-cvc",
    required: true,
    class: 'borgun-rpg-input borgun-rpg-card-cvc-input',
    autocorrect: "no",
    spellcheck: "no",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card code', 'borgun_rpg'),
    maxlength: "4",
    value: cardCodeValue,
    onChange: nextValue => {
      if (/^-?\d+$/.test(nextValue)) {
        setCardCodeValue(nextValue !== null && nextValue !== void 0 ? nextValue : '');
      } else {
        setCardCodeValue(cardCodeValue !== null && cardCodeValue !== void 0 ? cardCodeValue : '');
      }
    }
  })))];
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = props => {
  const {
    PaymentMethodLabel
  } = props.components;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PaymentMethodLabel, {
    text: label
  });
};

/**
 * Payment method config object.
 */
const BorgunRPGPaymentMethod = {
  name: 'borgun_rpg',
  label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Label, null),
  content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: (_settings$supports = settings?.supports) !== null && _settings$supports !== void 0 ? _settings$supports : []
  }
};
(0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__.registerPaymentMethod)(BorgunRPGPaymentMethod);
})();

/******/ })()
;
//# sourceMappingURL=view.js.map