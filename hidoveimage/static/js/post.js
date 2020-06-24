function hidoveimage() {

    this.config = {
        process_url: '',
        file_id: 'hidoveimage-file',
        add_id: 'hidoveimage-add',
        loading_tip_id: 'hidoveimage-loading-tip',
        completion_tip_id: 'hidoveimage-completion-tip',
        btns_id: 'hidoveimage-btns',
        tpl_id: 'hidoveimage-tpl-container',
        tools_id: 'hidoveimage-tools',
        unauthorize_id: 'hidoveimage-unauthorize',
        go_authorize_id: 'hidoveimage-go-authorize',
        reload_id: 'hidoveimage-reloadme',
        error_file_tip_id: 'hidoveimage-error-file-tip',
        error_files: 'hidoveimage-error-files',
        progress_id: 'hidoveimage-progress',
        progress_tx_id: 'hidoveimage-progress-tx',
        progress_bar_id: 'hidoveimage-progress-bar',
        thumbnail_remove_id: 'hidoveimage-thumbnail-remove',
        thumbnail_url_id: 'hidoveimage-thumbnail-url',
        thumbnail_preview_id: 'hidoveimage-thumbnail-preview',
        thumbnail_tip_id: 'hidoveimage-thumbnail-tip',
        accept_formats: ['png', 'jpg', 'gif'],
        storage_last_size_key: default_large_size,
        authorized: true,
        is_ssl: false,
        max_upload_size: 1024 * 2048,
        interval_timer: 3000,
        show_title: false,
        // thumbnail_size 		: 'thumb150',
        lang: {
            E00001: 'Error: ',
            E00002: 'Upload failed, please login weibo and try again. If you still failed, please contact the plugin author.',
            E00003: 'Sorry, plugin can not get authorized data, please try again later or contact plugin author.',
            M00001: 'Uploading {1}/{2}, please wait...',
            M00002: '{0} files have been uploaded, enjoy it.',
            M00003: 'Image URL: ',
            M00004: 'ALT attribute: ',
            M00005: 'Set ALT attribute text',
            M00006: 'Control: ',
            M00007: 'Insert to post with link',
            M00008: 'Insert to post image only',
            M00009: 'As custom meta feature image',
            M00010: 'Detects that files can not be uploaded:'
        },
        sizes: default_style,
        default_size: '2'
    }
    var cache = {
            errors: [],
            file_index: 0,
            file_count: 0,
            files: false,
            error_files: [],
            is_uploading: false
        },
        config = this.config,
        that = this,
        I = function (e) {
            return document.getElementById(e);
        },
        ready = function (fn) {
            if (document.readyState != 'loading') {
                fn();
            } else {
                document.addEventListener('DOMContentLoaded', fn);
            }
        };
    this.init = function () {
        ready(function () {
            cache.$loading_tip = I(config.loading_tip_id);
            cache.$add = I(config.add_id);
            cache.$file = I(config.file_id);
            cache.$reload = I(config.reload_id);
            cache.$completion_tip = I(config.completion_tip_id);
            cache.$tools = I(config.tools_id);
            cache.$tpl = I(config.tpl_id);
            cache.$unauthorize = I(config.unauthorize_id);
            cache.$error_file_tip = I(config.error_file_tip_id);
            cache.$error_files = I(config.error_files);
            cache.$progress = I(config.progress_id);
            cache.$progress_tx = I(config.progress_tx_id);
            cache.$progress_bar = I(config.progress_bar_id);
            cache.$thumbnail_remove = I(config.thumbnail_remove_id);
            cache.$thumbnail_tip = I(config.thumbnail_tip_id);
            cache.$thumbnail_preview = I(config.thumbnail_preview_id);
            cache.$thumbnail_url = I(config.thumbnail_url_id);

            cache.$loading_tip.style.display = 'none';
            cache.$add.style.display = 'block';

            thumbnail();
            bulk_insert_imgs();
            clear_list();
            /**
             * reload
             */

            /**
             * $file event
             */
            cache.$file.addEventListener('change', change_handle);
            cache.$file.addEventListener('drop', drop_handle);
            I('webbtn').addEventListener('click', web_upload)
        });
    }

    function web_upload() {
        var weburl = I('hidoveimage-webtext').value;
        if (!weburl) {
            I('hidoveimage-webtext').focus();
            return false;
        }
        beforesend_callback();
        if (token == '0') {
            var tt = format('请完善Hidove图床插件设置');
            uploading_tip('error', tt);
            return false;
        }
        jQuery.ajax({
            url: 'http://img.com/api/upload',
            type: "POST",
            data: {
                'url': weburl,
                'token': token,
                'folder': folder,
                'apiType': api_type,
                'privateStorage': private_storage,
            },
            dataType: "json",
            success: function (data, textStatus, xhr) {
                if (data && data.code === 200) {
                    url = data.data.url.distribute;
                    // console.log(url);
                    var args = {
                            'img_url': url,
                            'size': ''
                        },
                        $tmp = document.createElement('div');
                    $tmp.innerHTML = tpl(args);
                    var $tpl_table = $tmp.querySelector('table');

                    cache.$tpl.style.display = 'block';
                    cache.$tpl.insertBefore($tpl_table, cache.$tpl.firstChild);
                    $tpl_table.style.display = 'table';

                    /**
                     * bind thumb_change click
                     */
                    change_size(url);
                    /**
                     * focus alt attribute
                     */
                    var $img_alt = I('img-alt-' + get_id(url));
                    // if(!cache.$post_title)
                    // 	cache.$post_title = I('title');
                    // $img_alt.value = cache.$post_title.value;
                    $img_alt.value = '';
                    $img_alt.select();
                    /**
                     * bind thumb_insert click
                     */
                    insert_img(url, data);


                    /**
                     * show tools
                     */
                    if (!cache.$tools.style.display || cache.$tools.style.display === 'none')
                        cache.$tools.style.display = 'block';

                    cache.all_complete = true;
                    cache.is_uploading = false;
                    var tx = format(config.lang.M00002, 1);
                    uploading_tip('success', tx);
                    /**
                     * reset file input
                     */
                    cache.$file.value = '';

                    I('hidoveimage-webtext').value = '';


                }
            },
            complete: function (data, textStatus) {
                if (textStatus == 'error') {
                    // alert(textStatus);
                    cache.$progress.style.display = 'none';
                    cache.$add.style.display = 'block';
                    var tx = format('上传失败');
                    uploading_tip('error', tx);
                }

            }
        });


    }

    function reload_me() {
        cache.$loading_tip.style.display = 'block';
        cache.$unauthorize.style.display = 'none';
        var xhr = new XMLHttpRequest();
        xhr.open('get', config.process_url + '&type=check_authorize');
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 400) {
                var data;
                try {
                    data = JSON.parse(xhr.responseText)
                } catch (er) {
                    data = xhr.responseText
                }
                if (data && data.status === 'success') {
                    cache.$add.style.display = 'block';
                } else if (data && data.status === 'error') {
                    alert(data.msg);
                    cache.$unauthorize.style.display = 'block';
                } else {
                    alert(config.lang.E00003);
                    cache.$unauthorize.style.display = 'block';
                }
            } else {
                cache.$unauthorize.style.display = 'block';
                cache.$loading_tip.style.display = 'none';
            }
            cache.$loading_tip.style.display = 'none';
        };
        xhr.onerror = function () {
            cache.$unauthorize.style.display = 'block';
            cache.$loading_tip.style.display = 'none';
        };
    }

    function change_handle(e) {
        e.stopPropagation();
        e.preventDefault();
        cache.files = e.target.files.length ? e.target.files : e.originalEvent.dataTransfer.files;
        //start upload
        file_handle();
    }

    function drop_handle(e) {
        e.stopPropagation();
        e.preventDefault();
        cache.files = e.dataTransfer.files;
        //start upload
        file_handle();
    }

    function file_handle() {

        cache.file_count = cache.files.length;
        cache.file = cache.files[0];
        cache.file_index = 0;

        cache.$error_file_tip.style.display = 'none';
        cache.$error_file_tip.innerHTML = '';

        cache.$completion_tip.style.display = 'none';

        /**
         * start upload file
         */
        file_upload(cache.files[0]);
    }

    function file_upload(file) {
        cache.start_time = new Date();
        //var	reader = new FileReader();
        //reader.onload = function (e) {
        /**
         * exceed max upload size and in not allow file type
         */
        //if(file.size > config.max_upload_size || !/image/.test(file.type)){
        //error_file_tip(file);
        //}
        submission(file);

        //};
        //reader.readAsDataURL(file);
    }

    function error_file_tip(file) {
        if (file === 'hide') {
            cache.$error_files.innerHTML = '';
            cache.$error_file_tip.style.display = 'none';
            return;
        }
        var $tmp = document.createElement('div');
        $tmp.innerHTML += '<span class="error-file">' + file.name + '</span>';
        //var $content = $();
        //console.log(file.name);
        cache.$error_file_tip.appendChild($tmp.querySelector('span'));
        cache.$error_file_tip.style.display = 'block';
    }

    function submission(file) {
        //if(!cache.is_uploading)
        //return false;
        //console.log(cache.is_uploading);
        cache.is_uploading = true;
        beforesend_callback();

        var fd = new FormData(),
            xhr = new XMLHttpRequest();
        if (token === '0') {
            var tt = format('请完善Hidove图床插件设置');
            uploading_tip('error', tt);
            return false;
        }
        fd.append('image', file);
        fd.append('token', token);
        fd.append('folder', folder);
        fd.append('apiType', api_type);
        fd.append('privateStorage ', private_storage);

        //xhr.open('post',config.process_url + '&type=upload');
        xhr.open('post', api);
        xhr.send(fd);
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 304) {
                var data;
                try {
                    data = JSON.parse(this.responseText)
                } catch (err) {
                    data = this.responseText
                }
                complete_callback(data);
            } else {
                error_callback();
                cache.is_uploading = false;
            }
        };
        xhr.onerror = function () {
            error_callback();
            cache.is_uploading = false;
        };
        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                var percent = e.loaded / e.total * 100;
                cache.$progress_bar.style.width = percent + '%';
            }
        }
    }

    /**
     * upload_started
     */
    function upload_started(i, file, count) {
        var t = format(config.lang.M00001, i, count);
        uploading_tip('loading', t);
    }

    /**
     * The tip when pic is uploading
     *
     * @param string status 'loading','success' ,'error'
     * @param string text The content of tip
     * @return
     * @version 1.0.1
     */
    function uploading_tip(status, text) {
        /**
         * uploading status
         */
        if (!status || status === 'loading') {
            cache.$progress_tx.innerHTML = status_tip('loading', 'middle', text);
            cache.$progress.style.display = 'block';
            cache.$add.style.display = 'none';
            cache.$completion_tip.style.display = 'none';
            /**
             * success status
             */
        } else {
            cache.$completion_tip.innerHTML = status_tip(status, text);
            cache.$completion_tip.style.display = 'block';
            cache.$progress.style.display = 'none';
            cache.$add.style.display = 'block';
        }
    }

    function beforesend_callback() {
        var tx = format(config.lang.M00001, cache.file_index + 1, cache.file_count);
        cache.$progress_bar.style.width = '1%';
        uploading_tip('loading', tx);
    }

    function complete_callback(data) {

        cache.file_index++;
        /**
         * success
         */

        if (data && data.code === 200) {
            console.log(data);

            url = data.data.url.distribute;

            var args = {
                    'img_url': url,
                    'size': ''
                },
                $tmp = document.createElement('div');
            $tmp.innerHTML = tpl(args);
            var $tpl_table = $tmp.querySelector('table');

            cache.$tpl.style.display = 'block';
            cache.$tpl.insertBefore($tpl_table, cache.$tpl.firstChild);
            $tpl_table.style.display = 'table';

            /**
             * bind thumb_change click
             */
            change_size(url);
            /**
             * focus alt attribute
             */
            var $img_alt = I('img-alt-' + get_id(url));
            // if(!cache.$post_title)
            //	cache.$post_title = I('title');
            // $img_alt.value = cache.$post_title.value;
            $img_alt.value = '';
            $img_alt.select();
            /**
             * bind thumb_insert click
             */
            insert_img(url, data);


            /**
             * show tools
             */
            if (!cache.$tools.style.display || cache.$tools.style.display === 'none')
                cache.$tools.style.display = 'block';


            /**
             * check all thing has finished, if finished
             */
            if (cache.file_count === cache.file_index) {
                cache.all_complete = true;
                cache.is_uploading = false;
                var tx = format(config.lang.M00002, cache.file_count);
                uploading_tip('success', tx);
                /**
                 * reset file input
                 */
                cache.$file.value = '';

                /**
                 * upload next pic
                 */
            } else {
//console.log(cache.file_count,cache.file_index);
                upload_next(cache.files[cache.file_index]);
            }
            /**
             * no success
             */
        } else {
            /**
             * notify current file is error
             */
            if (cache.file_index > 0) {
                error_file_tip(cache.files[cache.file_index - 1]);
            }
            /**
             * if have next file, continue to upload next file
             */
            if (cache.file_count > cache.file_index) {
                upload_next(cache.files[cache.file_index]);
                /**
                 * have not next file, all complete
                 */
            } else {
                cache.is_uploading = false;
                if (data && data.status === 'error') {
                    error_callback(data.msg);
                } else {
                    error_callback(config.lang.E00002);
                    console.error(data);
                }
                /**
                 * reset file input
                 */
                cache.$file.value = '';

            }
        }
    }

    function upload_next(next_file) {
        /**
         * check interval time
         */
        var end_time = new Date(),
            interval_time = end_time - cache.start_time,
            timeout = config.interval - interval_time,
            timeout = timeout < 0 ? 0 : timeout;
        /**
         * if curr time > interval time, upload next pic right now
         */
        setTimeout(function () {
            console.log('next');
            file_upload(next_file);
        }, timeout);
    }

    function error_callback(msg) {
        msg = msg ? msg : config.lang.E00002;
        uploading_tip('error', msg);
    }

    /**
     * get_img_url_by_size
     *
     * @params string size The img size
     * @version 1.0.2
     */
    function get_img_url_by_size(size, img_url) {
        var temp = img_url.indexOf('@');
        if (temp > 1) {
            img_url = img_url.substr(0, temp);
        }
        var end;

        url = img_url + end;
        return url;
    }

    /**
     * get_id
     *
     * @params string Image url
     * @return string The ID
     * @version 1.0.0
     */
    function get_id(img_url) {
        var id = img_url.split('/'),
            id = id[id.length - 1].split('.')[0];
        return id;
    }

    /**
     * change_size
     *
     * @params string img_url
     * @return n/a
     * @version 1.0.1
     */
    function change_size(img_url) {
        var id = get_id(img_url);
        for (var key in config.sizes) {
            /**
             * start bind
             */
            I('' + key + '-' + id).addEventListener('click', function () {
                var $this = this,
                    img_size_url = img_url;
                I('img-url-' + id).value = img_size_url;
                I('img-link-' + id).setAttribute('href', img_size_url);
                /**
                 * set localStorage for next default clicked
                 */
                localStorage.setItem(config.storage_last_size_key, $this.value);
            });
        }
    }

    /**
     * send_to_editor
     *
     * @return
     * @version 1.0.0
     */
    function send_to_editor(h) {
        var ed, mce = typeof (tinymce) != '', qt = typeof (QTags) != 'undefined';

        if (typeof (wpActiveEditor) == 'undefined') {
            if (mce && tinymce.activeEditor) {
                ed = tinymce.activeEditor;
                wpActiveEditor = ed.id;
            } else if (!qt) {
                return false;
            }
        } else if (mce) {
            if (tinymce.activeEditor && (tinymce.activeEditor.id == 'mce_fullscreen' || tinymce.activeEditor.id == 'wp_mce_fullscreen'))
                ed = tinymce.activeEditor;
            else
                ed = tinymce.get(wpActiveEditor);
        }

        if (ed && !ed.isHidden()) {
            // restore caret position on IE
            if (tinymce.isIE && ed.windowManager.insertimagebookmark)
                ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);

            if (h.indexOf('[caption') !== -1) {
                if (ed.wpSetImgCaption)
                    h = ed.wpSetImgCaption(h);
            } else if (h.indexOf('[gallery') !== -1) {
                if (ed.plugins.wpgallery)
                    h = ed.plugins.wpgallery._do_gallery(h);
            } else if (h.indexOf('[embed') === 0) {
                if (ed.plugins.wordpress)
                    h = ed.plugins.wordpress._setEmbed(h);
            }

            ed.execCommand('mceInsertContent', false, h);
        } else if (qt) {
            QTags.insertContent(h);
        } else {
            I(wpActiveEditor).value += h;
        }

        try {
            tb_remove();
        } catch (e) {
        }

    }

    function get_split_str() {
        var $split = I('hidoveimage-split');
        if (!$split[0]) return '';
        switch ($split.value) {
            case 'nextpage':
                return '<!--nextpage-->';
                break;
            default:
                return '';
        }
    }

    /**
     * bulk insert images to tpl
     */
    function bulk_insert_imgs() {
        /**
         * get_tpl
         */
        var get_tpl = function (url, with_link) {
            if (typeof url == 'undefined') return false;
            var tpl,
                large_size_url = url,
                id = get_id(url),

                new_img_src = I('img-url-' + id).value,
                img_alt_val = I('img-alt-' + id).value,
                img_alt = ' alt="' + img_alt_val + '"',
                img_title = config.show_title ? ' title="' + img_alt_val + '" ' : '',
                img = '<img src="' + new_img_src + '" ' + img_alt + img_title + '/>';

            if (with_link === true) {
                tpl = '<a href="' + large_size_url + '" target="_blank">' + img + '</a>';
            } else {
                tpl = img;
            }
            /**
             * wrap the <p>
             */
            tpl = '<p>' + tpl + '</p>';
            return tpl;
        };
        /**
         * with link
         */
        I('hidoveimage-insert-list-with-link').addEventListener('click', event_insert);
        I('hidoveimage-insert-list-without-link').addEventListener('click', event_insert);

        function event_insert(e) {
            e.preventDefault();
            var $link = this,
                $img_urls = cache.$tpl.querySelectorAll('input.img-url');
            if (!$img_urls[0])
                return false;
            var tpl = [];
            for (var i = 0, len = $img_urls.length; i < len; i++) {
                var $this = $img_urls[i],
                    url = $this.value;
                if ($link.id === 'hidoveimage-insert-list-with-link') {
                    tpl_content = get_tpl(url, true);
                } else {
                    tpl_content = get_tpl(url, false);
                }
                tpl.push(tpl_content);
            }

            tpl = tpl.join(get_split_str());
            /**
             * send to editor
             */
            send_to_editor(tpl);
        };
    }

    /**
     * insert_img
     *
     * @params string img_url
     * @return n/a
     * @version 2.0.1
     */
    function insert_img(url, data) {
        var id = get_id(url),
            tpl = '',
            $img_url = I('img-url-' + id)
        $img_url.addEventListener('click', function () {
            this.select();
        }),
            large_size_url = url;

        function get_img() {
            var img_alt_val = I('img-alt-' + id).value,
                img_alt = ' alt="' + img_alt_val + '"',
                img_title = config.show_title ? ' title="' + img_alt_val + '" ' : '';
            return '<img src="' + $img_url.value + '" ' + img_alt + img_title + '/>';

        }

        /**
         * with link
         */
        I('btn-with-link-' + id).addEventListener('click', function (e) {
            e.preventDefault();

            tpl = '<a href="' + large_size_url + '" target="_blank">' + get_img() + '</a>';
            /**
             * send to editor
             */
            send_to_editor(tpl);
        });
        /**
         * without link
         */
        I('btn-without-link-' + id).addEventListener('click', function (e) {
            e.preventDefault();
            tpl = get_img();
            /**
             * send to editor
             */
            send_to_editor(tpl);
        });
        /**
         * as feature
         */
        I('btn-as-feature-image' + id).addEventListener('click', function () {
            var $this = this;
            if ($this.classList.contains('button-primary')) {
                $this.classList.remove('button-primary');
                thumbnail_remove();
            } else {
                var $btns = document.querySelectorAll('.btn-as-feature-image');
                for (var i = 0, len = $btns.length; i < len; i++) {
                    $btns[i].classList.remove('button-primary');
                }
                $this.classList.add('button-primary');
                thumbnail_set(url);
            }
        });
    }

    function thumbnail_set(url) {

        cache.$thumbnail_tip.style.display = 'none';
        cache.$thumbnail_url.value = url;
        cache.$thumbnail_preview.style.display = 'block';
        cache.$thumbnail_preview.innerHTML = '<img src="' + url + '" alt="">';
        cache.$thumbnail_remove.style.display = 'block';
    }

    function thumbnail_remove() {
        cache.$thumbnail_remove.style.display = 'none';
        cache.$thumbnail_tip.style.display = 'block';
        cache.$thumbnail_url.value = '';
        cache.$thumbnail_preview.style.display = 'none'
        var $img = cache.$thumbnail_preview.querySelector('img');
        $img.parentNode.removeChild($img);
    }

    function thumbnail() {
        cache.$thumbnail_remove.addEventListener('click', function () {
            var $btns = document.querySelectorAll('.btn-as-feature-image');
            for (var i = 0, len = $btns.length; i < len; i++) {
                $btns[i].classList.remove('button-primary');
            }
            thumbnail_remove();
        });
    }

    /**
     * tietuku_pic_upload.hook.tpl
     *
     * @params object args
     * args = {
     * 		'img_url' : 'http://....w1e1iyntr4oaj.jpg',
     * 		'size' 	: see tietuku_pic_upload.hook.get_img_url_by_size()
     * }
     * @return string HTML
     * @version 1.0.1
     */
    function tpl(args) {
        if (!args) return false;
        var id = get_id(args.img_url),
            img_url = args.img_url,
            size_string = '',
            i = 0,
            checked = '',
            last_img_size = localStorage.getItem(config.storage_last_size_key);

        if (!last_img_size)
            last_img_size = config.default_size;

        for (var key in config.sizes) {

            checked = last_img_size === key ? ' checked="checked" ' : '';

            /**
             * content
             */

            size_string +=
                '<label for="' + key + '-' + id + '" title="' + config.sizes[key] + '" class="sizes-label">' +
                '<input id="' + key + '-' + id + '" name="tietuku_pic[size-' + id + ']" class="size-input" type="radio" value="' + key + '"' + checked + '/>' +
                '<span>' + key + '</span>' +
                '</label>' +
                '';
        }
        var content =
            '<table class="tpl-table" id="table-' + id + '"><tbody>' +
            '<tr>' +
            '<th>' +
            '<a id="img-link-' + id + '" href="' + img_url + '" target="_blank" class="img-preview-link"><img id="img-preview-' + id + '" class="img-preview" src="' + img_url + '" alt="preview"/></a>' +
            '</th>' +
            '<td>' +
            '<div class="size-group" style="display:none;">' +
            size_string +
            '</div>' +
            '<input id="img-url-' + id + '" type="text" class="img-url regular-text" value="' + img_url + '" name="tietuku_pic[img-url-' + id + ']" readonly="true"/>' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<th>' +
            '<label for="img-alt-' + id + '">' + config.lang.M00004 + '</label>' +
            '</th>' +
            '<td>' +
            '<input id="img-alt-' + id + '" type="text" class="img-alt regular-text" placeholder="' + config.lang.M00005 + '" value=""/>' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<th>' + config.lang.M00006 + '</th>' +
            '<td scope="col" colspan="2">' +
            '<a id="btn-with-link-' + id + '" href="javascript:;" class="button button-primary">' + config.lang.M00007 + '</a> ' +
            '<a id="btn-without-link-' + id + '" href="javascript:;" class="button">' + config.lang.M00008 + '</a> ' +
            '<a href="javascript:;" id="btn-as-feature-image' + id + '" class="button btn-as-feature-image">' + config.lang.M00009 + '</a>' +
            '</td>' +
            '</tr>' +
            '</tbody></table>' +
            '';
        return content;
    }

    function format() {
        var ary = [];
        for (var i = 1; i < arguments.length; i++) {
            ary.push(arguments[i]);
        }
        return arguments[0].replace(/\{(\d+)\}/g, function (m, i) {
            return ary[i];
        });
    }

    /**
     * status_tip
     *
     * @param mixed
     * @return string
     * @version 1.1.0
     */
    function status_tip() {
        var defaults = ['type', 'size', 'content', 'wrapper'],
            types = ['loading', 'success', 'error', 'question', 'info', 'ban', 'warning'],
            sizes = default_style,
            wrappers = ['div', 'span'],
            type = null,
            icon = null,
            size = null,
            wrapper = null,
            content = null,
            args = arguments;
        switch (args.length) {
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
                for (var i in args) {
                    eval(defaults[i] + ' = args[i];');
                }
        }
        wrapper = wrapper || wrappers[0];
        type = type || types[0];
        size = size || sizes[0];

        switch (type) {
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

    /**
     * Clean tpl list
     */
    function clear_list() {
        cache.$clear_list = I('hidoveimage-clear-list');
        if (!cache.$clear_list)
            return false;

        cache.$clear_list.addEventListener('click', function () {
            cache.$tools.style.display = 'none';
            cache.$completion_tip.style.display = 'none';
            cache.$tpl.innerHTML = '';
        });
    }

}
