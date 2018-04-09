<style>
    .image-original {
        max-height: 200px;
        border: 3px solid #666;
    }
    .image-preview {
        max-height: 200px;
        border: 3px solid #666;
    }
    .image-preview:hover {
        border: 3px solid #999;
    }
</style>

<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/image-styles')">@lang('Image Styles')</a></li>
        <li class="uk-active"><span>@lang('Image Style')</span></li>
    </ul>
</div>

<div class="uk-margin-top" riot-view>
    <form id="account-form" class="uk-form uk-grid uk-grid-gutter" onsubmit="{ submit }">

        <div class="uk-width-medium-1-4">
            <div class="uk-panel uk-panel-box uk-panel-card">

                <div class="uk-margin">
                    <label class="uk-text-small">@lang('Name')</label>
                    <input class="uk-width-1-1 uk-form-large" type="text" ref="name" bind="style.name" pattern="[a-zA-Z0-9_]+" required="">
                    <p class="uk-text-small uk-text-muted"> @lang('Only alpha nummeric value is allowed') </p>
                </div>

                <div class="uk-margin">
                    <label class="uk-text-small">@lang('Description')</label>
                    <textarea class="uk-width-1-1 uk-form-large" name="description" bind="style.description" bind-event="input" rows="5"></textarea>
                </div>

                <div class="uk-margin">
                    <label class="uk-text-small">@lang('Quality')</label>
                    <input class="uk-width-1-1 uk-form-large" type="text" ref="quality" bind="style.quality" pattern="[0-9]+" required="">
                    <p class="uk-text-small uk-text-muted"> @lang('Use a numeric value between 0 and 100') </p>
                </div>

                <div class="uk-margin">
                    <field-boolean bind="style.base64" title="@lang('Base64 output')" label="@lang('Base64 output')"></field-boolean>
                </div>

                <div class="uk-margin">
                    <field-boolean bind="style.domain" title="@lang('Domain output')" label="@lang('Domain output')"></field-boolean>
                </div>

            </div>
        </div>

        <div class="uk-width-medium-3-4">

            <div class="uk-form-row">
                <div class="uk-margin">
                    <label class="uk-text-small">@lang('Resize Mode')</label>
                </div>

                <div class="uk-panel uk-panel-box uk-panel-card">

                    <div class="uk-grid uk-grid-small">

                        <div class="uk-width-1-4">
                            <div class="uk-form-select" data-uk-form-select>
                                <label class="uk-text-small">@lang('Method:')</label>
                                <input class="uk-width-1-1 uk-form-small uk-form-blank" value="{ style.mode }">
                                <select bind="style.mode" required="required">
                                    <option value="resize">@lang('Resize')</option>
                                    <option value="thumbnail">@lang('Thumbnail')</option>
                                    <option value="bestFit">@lang('Best Fit')</option>
                                    <option value="fitToWidth">@lang('Fit to Width')</option>
                                    <option value="fitToHeight">@lang('Fit to Height')</option>
                                </select>
                            </div>
                        </div>

                        <div class="uk-flex-item-1 uk-flex">
                            <div class="uk-panel">
                                <label class="uk-text-small">@lang('Width:')</label>
                                <input class="uk-width-1-1 uk-form-small uk-form-blank" size="4" maxlength="4" type="text" bind="style.width" placeholder="width" pattern="[0-9_]+" required="{ isRequired('width') }">
                            </div>
                        </div>

                        <div class="uk-flex-item-1 uk-flex">
                            <div class="uk-panel">
                                <label class="uk-text-small">@lang('Height:')</label>
                                <input class="uk-width-1-1 uk-form-small uk-form-blank" size="4" maxlength="4" type="text" bind="style.height" placeholder="height" pattern="[0-9_]+" required="{ isRequired('height') }">
                            </div>
                        </div>

                        <div class="uk-flex-item-1 uk-flex" if="{ style.mode == 'thumbnail' }">
                            <div class="uk-form-select" data-uk-form-select>
                                <label class="uk-text-small">@lang('Anchor:')</label>
                                <input class="uk-width-1-1 uk-form-small uk-form-blank" value="{ style.anchor }">
                                <select bind="style.anchor" required="required">
                                    <option value="center">@lang('Center')</option>
                                    <option value="top">@lang('Top')</option>
                                    <option value="bottom">@lang('Bottom')</option>
                                    <option value="left">@lang('Left')</option>
                                    <option value="right">@lang('Right')</option>
                                    <option value="top left">@lang('Top Left')</option>
                                    <option value="top right">@lang('Top Right')</option>
                                    <option value="bottom left">@lang('Bottom Left')</option>
                                    <option value="bottom right">@lang('Bottom Right')</option>
                                </select>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <div class="uk-form-row">
                <div class="uk-margin">
                    <label class="uk-text-small">@lang('Effects')</label>
                </div>
                <cp-effectsmanager bind="style.effects"></cp-effectsmanager>
            </div>

            <div class="uk-form-row uk-grid">
                <div class="uk-grid-margin uk-width-1-3">
                    <div class="uk-text-small">@lang('Original:')</div>
                    <div>
                        <img ref="original" src="@base('imagestyles:assets/media/picture.png')" class="image-original" />
                    </div>
                </div>
                <div class="uk-grid-margin uk-width-1-3">
                    <div class="uk-text-small">@lang('Preview:')</div>
                    <div>
                        <a onclick="{ openPreview }">
                            <img ref="preview" src="@base('imagestyles:assets/media/picture.png')" class="image-preview"/>
                        </a>
                    </div>
                </div>
            </div>

            <div class="uk-margin-large-top">
                <button class="uk-button uk-button-large uk-width-1-3 uk-button-primary uk-margin-right">@lang('Save')</button>
                <a href="@route('/image-styles')">@lang('Cancel')</a>
            </div>
        </div>

    </form>


    <script type="view/script">

        var $this = this;

        this.mixin(RiotBindMixin);

        this.style = {{ json_encode($style) }};

        this.on('update', function(){
            this.updatePreview();
            if (this.style.mode === 'fitToWidth') {
                this.style.height = '';
            } else if (this.style.mode === 'fitToHeight') {
                this.style.width = '';
            }
            if (this.style._id) {
                this.refs.name.disabled = true;
            }
        });

        this.on('mount', function() {
            this.trigger('update');
            // bind clobal command + save
            Mousetrap.bindGlobal(['command+s', 'ctrl+s'], function(e) {
                e.preventDefault();
                $this.submit();
                return false;
            });
        });

        submit(e) {
            if(e) e.preventDefault();

            App.callmodule('imagestyles:saveStyle', [this.style.name, this.style]).then(function(data) {
               if (data.result) {
                   App.ui.notify("Saving successful", "success");
                   $this.style = data.result;
                   $this.update();
                } else {
                    App.ui.notify("Saving failed.", "danger");
                }
            });
        }

        isRequired(field) {
            if (field == 'width') {
                return (['resize', 'thumbnail', 'fitToWidth'].indexOf(this.style.mode) != -1);
            } else {
                return (['resize', 'thumbnail', 'fitToHeight'].indexOf(this.style.mode) != -1);
            }
        }

        updatePreview() {
            const src = this.refs.original.src.replace(/^.*\/\/[^\/]+/, '');

            App.callmodule('imagestyles:previewStyle', [src, this.style]).then(function(data) {
                if (data && data.result && !data.result.error) {
                    $this.refs.preview.src = data.result.replace('\/', '');
                } else {
                    $this.refs.preview.src = '';
                }
            });
        }

        openPreview(e) {
            e.preventDefault();
            var newTab = window.open();
            newTab.document.body.innerHTML = '<img src="' + e.target.getAttribute('src') + '">';
        }

    </script>

</div>
