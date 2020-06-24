var plugin_backend = function(args){
	'use strict';
	var exports = {};
	/**
	 * admin page init js
	 */
	exports.init = function(args){
		jQuery(document).ready(function(){
			var ootab = new exports.backend_tab({
				done : args.done,
				custom : args.custom,
				tab_title: args.tab_title
			});
		});
	};
	/**
	 * Select Text
	 * 
	 * 
	 * @version 1.0.0
	 * 
	 */
	exports.select_text = {
		config : {
			input_id : '.text-select'
		},
		init : function(){
			var $inputs = document.querySelectorAll(exports.select_text.config.input_id);

			if(!$inputs[0])
				return false;

			Array.prototype.forEach.call($inputs, function($input,i){
				$input.addEventListener('click', function (e) {
					this.select();
				},false);
			});
		}
	};

	exports.backend_tab = function(args){
		this.config = {
			tab_id : '#backend-tab',
			tab_cookie_id : 'backend_default_tab',
		}

		var that = this,
			$tab = jQuery(that.config.tab_id);
		if(!$tab[0]) return false;

		var current_tab = exports.cookie.get(that.config.tab_cookie_id);
		if(!current_tab) current_tab = 1;

		
		$tab.KandyTabs({
			delay:100,
			resize:false,
			current:current_tab,
			custom:function(b,c,i,t){
				exports.cookie.set(that.config.tab_cookie_id,i+1);
				args.custom(b,c,i,t);
			},
			done:function($btn,$cont,$tab){
				jQuery('.backend-tab-loading').hide();
				$btn.eq(0).before('<span class="tab-title">' + args.tab_title +'</span>');
				$tab.show();
				args.done($btn,$cont,$tab);
				exports.select_text.init();
			}
		})
	};
	/** 
	 * cookie
	 */
	exports.cookie = {
		/**
		 * get_cookie
		 * 
		 * @params string
		 * @return string
		 * @version 1.0.0
		 */
		get : function(c_name){
			var i,x,y,ARRcookies=document.cookie.split(';');
			for(i=0;i<ARRcookies.length;i++){
				x=ARRcookies[i].substr(0,ARRcookies[i].indexOf('='));
				y=ARRcookies[i].substr(ARRcookies[i].indexOf('=')+1);
				x=x.replace(/^\s+|\s+$/g,'');
				if(x==c_name) return unescape(y);
			}
		},
		/**
		 * set_cookie
		 * 
		 * @params string cookie key name
		 * @params string cookie value
		 * @params int the expires days
		 * @return n/a
		 * @version 1.0.0
		 */
		set : function(c_name,value,exdays){
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + exdays);
			var c_value=escape(value) + ((exdays==null) ? '' : '; expires=' + exdate.toUTCString());
			document.cookie = c_name + '=' + c_value;
		}
	};
	/**
	 * init
	 */
	exports.init(args);

};