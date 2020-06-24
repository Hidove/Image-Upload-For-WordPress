function hidoveimage_admin(){

if(!$) $ = jQuery;

this.config = {

	process_url : '',
	
	lang : {
		E00001 : 'Error code: ',
		E00002 : 'Program error, can not continue to operate. Please try again or contact author. ',
		
		M00001 : 'Getting backup config data, please wait... ',
		M00002 : 'Current processing: ',
		M00003 : 'Downloading, you can restore the pictures to post after the download is complete. ',
		M00005 : 'Download completed, you can perform a restore operation. ',
		M00006 : 'Current file has been downloaded, skipping it. ',

		M00010 : 'The data is being restored , please wait... ',
		
	}
}
var cache = {},
	that = this;

this.init = function(){
	$(document).ready(function(){
		tab();
		var oobackup = new backup(),
			oorestore = new restore();
		oobackup.init();
		oorestore.init();
	})
}

function backup(){
	
	this.init = function(){
		cache.$backup_btn = $('#hidoveimage-backup-btn');
		cache.$backup_btns = $('#hidoveimage-backup-btns');
		cache.$backup_tip = $('#hidoveimage-backup-tip');
		cache.$backup_progress_bar = $('#hidoveimage-backup-progress-bar');
		
		cache.$backup_btn.on('click',function(){
			cache.$backup_btns.hide();
			tip('loading',that.config.lang.M00001);
			$.ajax({
				url : that.config.process_url,
				data : {
					type : 'get_backup_data'
				},
				dataType : 'json'
			}).done(function(data){
				backup_done(data);
			}).error(function(data){
				tip('error',that.config.lang.E00002 + '<br/>' + data.responseText);
				cache.$backup_btns.show();
			}).always(function(){
				// cache.$backup_btns.show();
			});
		});
	}
	/** 
	 * backup done
	 */
	function backup_done(data){
		if(data && data.status === 'success'){
			var posts = data.posts;
			cache.imgs = [];
			cache.img_index = 0;
			for(var i in posts){
				if(posts[i]['imgs']){
					for(var j in posts[i]['imgs']){
						cache.imgs.push({
							url : posts[i]['imgs'][j],
							post_id : posts[i]['id']
						});
					}
				}
			}
			/** 
			 * download start, come on~!!
			 */
			ajax_download();
			
		}else if(data && data.status === 'error'){
			tip('error',data.msg);
			cache.$backup_btns.show();
		}else{
			tip('error',that.config.lang.E00002);
			cache.$backup_btns.show();
		}
	}
	function ajax_download(){
		var img = cache.imgs[cache.img_index],
			imgs_len = cache.imgs.length,
			next_img_index = cache.img_index + 1;
		/** 
		 * progress
		 */
		progress(cache.$backup_progress_bar,next_img_index,imgs_len);
		/** 
		 * all complete
		 */
		if(imgs_len < next_img_index){
			tip('success',that.config.lang.M00005);
			cache.$backup_btns.show();
			return false;
		}
		if(cache.img_index === 0) tip('loading',that.config.lang.M00002 + (cache.img_index + 1) +'/' + imgs_len);
		cache.img_index++;
		$.ajax({
			url : that.config.process_url,
			data : {
				post_id : img.post_id,
				img_url : img.url,
				type : 'download'
			},
			dataType : 'json'
		}).done(function(data){
			if(data && data.status === 'success'){
				/** 
				 * download next
				 */
				if(data.skip){
					tip('loading',that.config.lang.M00006 + ' ' + that.config.lang.M00002 + next_img_index + '/' + imgs_len);
				}else{
					tip('loading',that.config.lang.M00002 + next_img_index + '/' + imgs_len);
				}
				ajax_download();
			}else if(data && data.status === 'error'){
				tip('error',data.msg + ' ' + that.config.lang.M00002 + next_img_index + '/' + imgs_len);
				ajax_download();
			}else{
				tip('error',that.config.lang.E00002);
				console.log(data);
			}
		}).error(function(data){
			tip('error',that.config.lang.E00002 + '<br/>' + data.responseText);
			cache.$backup_btns.show();
			// ajax_download();
		}).always(function(){
		
		});
	}
	function tip(t,s){
		if(t === 'hide'){
			cache.$backup_tip.hide();
		}else{
			cache.$backup_tip.html(status_tip(t,s)).show();
		}
	}
}

function restore(){
	this.init = function(){
		cache.$server_to_space_btn = $('#hidoveimage-restore-server-to-host-btn');
		cache.$space_to_server_btn = $('#hidoveimage-restore-host-to-server-btn');
		cache.$restore_progress_bar = $('#hidoveimage-restore-progress-bar');
		cache.$restore_tip = $('#hidoveimage-restore-tip');
		cache.$restore_btns = $('#hidoveimage-restore-btns');
		
		server_to_space();
		
		space_to_server();
	
	}
	
	function server_to_space(){
		cache.$server_to_space_btn.on('click',function(){
			tip('loading',that.config.lang.M00010);
			$.ajax({
				url : that.config.process_url,
				data : {
					type : 'restore-sina-to-local'
				},
				dataType : 'json'
			}).done(function(data){
				done(data);
			}).error(function(data){
				tip('error',that.config.lang.E00002 + '<br/>' + data.responseText);
			}).always(function(){
				cache.$restore_btns.show();
			});
		});
	}
	function space_to_server(){
		cache.$space_to_server_btn.on('click',function(){
			tip('loading',that.config.lang.M00010);
			$.ajax({
				url : that.config.process_url,
				data : {
					type : 'restore-local-to-sina'
				},
				dataType : 'json'
			}).done(function(data){
				done(data);
			}).error(function(data){
				tip('error',that.config.lang.E00002 + '<br/>' + data.responseText);
			}).always(function(){
				cache.$restore_btns.show();
			});
		});
	}
	function done(data){
		if(data && data.status){
			tip(data.status,data.msg);
		}else{
			tip('error',that.config.E00002);
		}
	}
	function tip(t,s){
		if(t === 'hide'){
			cache.$restore_tip.hide();
			cache.$restore_btns.show();
		}else{
			cache.$restore_btns.hide();
			cache.$restore_tip.html(status_tip(t,s)).show();
		}
	}
	
}



function progress($bar,curr,total){
	$bar.show();
	if(curr == 0){
		$bar.width('1');
		return;
	}else if(curr > total){
		curr = total;
	}
	$bar.animate({
		width :  (curr/total)*100 + '%'
	},1000);
}
	
function tab(){
	var $tab = $('#backend-tab');
	if(!$tab[0]) return;
	
	$tab.KandyTabs({
		done : function(){
			$tab.show();
			$('.backend-tab-loading').hide();
		}
	});
}

/**
 * status_tip
 *
 * @param mixed
 * @return string
 * @version 1.1.0
 */
function status_tip(){
	var defaults = ['type','size','content','wrapper'],
		types = ['loading','success','error','question','info','ban','warning'],
		sizes = ['small','middle','large'],
		wrappers = ['div','span'],
		type = null,
		icon = null,
		size = null,
		wrapper = null,
		content = null,	
		args = arguments;
		switch(args.length){
			case 0:
				return false;
			/** 
			 * only content
			 */
			case 1:
				content = args[0];
				break;
			/** 
			 * only type & content
			 */
			case 2:
				type = args[0];
				content = args[1];
				break;
			/** 
			 * other
			 */
			default:
				for(var i in args){
					eval(defaults[i] + ' = args[i];');
				}
		}
		wrapper = wrapper || wrappers[0];
		type = type ||  types[0];
		size = size ||  sizes[0];
	
		switch(type){
			case 'success':
				icon = 'smiley';
				break;
			case 'error' :
				icon = 'no';
				break;
			case 'info':
			case 'warning':
				icon = 'info';
				break;
			case 'question':
			case 'help':
				icon = 'editor-help';
				break;
			case 'ban':
				icon = 'minus';
				break;
			case 'loading':
			case 'spinner':
				icon = 'update';
				break;
			default:
				icon = type;
		}

		var tpl = '<' + wrapper + ' class="tip-status tip-status-' + size + ' tip-status-' + type + '"><span class="dashicons dashicons-' + icon + '"></span><span class="after-icon">' + content + '</span></' + wrapper + '>';
		return tpl;
}
}