<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/settings')">@lang('Settings')</a></li>
        <li class="uk-active"><span>@lang('Image Styles')</span></li>
    </ul>
</div>

<div class="uk-margin-top" riot-view>

    @if($app->module('cockpit')->hasaccess('imagestyles', 'manage.view'))
    <div class="uk-form uk-clearfix" show="{!loading}">

        <span class="uk-form-icon">
            <i class="uk-icon-filter"></i>
            <input type="text" class="uk-form-large uk-form-blank" ref="txtfilter" placeholder="@lang('Filter by name...')" onkeyup="{ updatefilter }">
        </span>

        <div class="uk-float-right">
            @if($app->module('cockpit')->hasaccess('imagestyles', 'manage.admin'))
            <a class="uk-button uk-button-primary uk-button-large" href="@route('/image-styles/style')">
                <i class="uk-icon-plus-circle uk-icon-justify"></i> @lang('Add')
            </a>
            @endif
        </div>

    </div>
    @endif

    <div class="uk-text-xlarge uk-text-center uk-text-primary uk-margin-large-top" show="{ loading }">
        <i class="uk-icon-spinner uk-icon-spin"></i>
    </div>

    <div class="uk-text-large uk-text-center uk-margin-large-top uk-text-muted" show="{ !loading && styles.length == 0 }">
        <img class="uk-svg-adjust" src="@url('assets:app/media/icons/database.svg')" width="100" height="100" alt="@lang('Image Styles')" data-uk-svg />
        <p>@lang('No image styles found')</p>
    </div>

    <div class="uk-grid uk-grid-match uk-grid-gutter uk-grid-width-1-1 uk-grid-width-medium-1-3 uk-grid-width-large-1-4 uk-margin-top">

        <div each="{ style, name in styles }" show="{ infilter(style) }">

            <div class="uk-panel uk-panel-box uk-panel-card">
                <div class="uk-grid uk-grid-small">
                    @if($app->module('cockpit')->hasaccess('imagestyles', 'manage.admin'))
                    <div data-uk-dropdown="delay:300">
                        <a class="uk-icon-cog"" href="@route('/image-styles/style')/{name}"></a>
                        <a class="uk-text-bold uk-flex-item-1 uk-text-center uk-link-muted" href="@route('/image-styles/style')/{name}">{ name }</a>
                        <div class="uk-dropdown">
                            <ul class="uk-nav uk-nav-dropdown">
                                <li class="uk-nav-header">@lang('Actions')</li>
                                <li class="uk-nav-divider"></li>
                                <li><a href="@route('/image-styles/style')/{name}">@lang('Edit')</a></li>
                                <li class="uk-nav-item-danger"><a class="uk-dropdown-close" onclick="{ parent.remove }">@lang('Delete')</a></li>
                            </ul>
                        </div>
                    </div>
                    @else
                    <span class="uk-text-large uk-display-block">{ name }</span>
                    @endif
                </div>

                <div class="uk-margin-top">
                    <div class="uk-margin-small-bottom">
                        <span class="uk-text-small uk-display-block">{ style.description }</span>
                    </div>
                    <div class="uk-margin-small-bottom">
                        <span class="uk-text-small uk-text-uppercase uk-text-muted">@lang('Mode')</span>
                        <span class="uk-text-small uk-display-block">{ style.mode } { getStyleInfo(style) }</span>
                    </div>
                    <div class="uk-margin-small-bottom">
                        <span class="uk-text-small uk-text-uppercase uk-text-muted">@lang('Quality')</span>
                        <span class="uk-text-small uk-display-block">{ style.quality }%</span>
                    </div>
                    <div class="uk-margin-small-bottom">
                        <span class="uk-text-small uk-text-uppercase uk-text-muted">@lang('Effects')</span>
                        <span class="uk-text-small uk-display-block" each="{effect,idx in style.effects}">{ effect.type }</span>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script type="view/script">

        var $this = this;

        this.ready  = false;
        this.styles = {{ json_encode($styles) }};

        remove(e, style) {
            style = e.item.style;

            App.ui.confirm("Are you sure?", function() {
                App.callmodule('imagestyles:removeStyle', style.name).then(function(data) {
                    App.ui.notify("Image Style removed", "success");
                    delete $this.styles[style.name];
                    $this.update();
                });
            });
        }

        updatefilter(e) {
        }

        infilter(imagestyle, value, name, label) {
            if (!this.refs.txtfilter.value) {
                return true;
            }

            value = this.refs.txtfilter.value.toLowerCase();
            name  = [imagestyle.name.toLowerCase(), imagestyle.description.toLowerCase()].join(' ');

            return name.indexOf(value) !== -1;
        }

        getStyleInfo(style) {
            switch (style.mode) {
                case 'resize':
                case 'bestFit':
                    return style.width + "x" + style.height
                case 'thumbnail':
                    return style.width + "x" + style.height + " " + style.anchor;
                case "fitToWidth":
                    return style.width + "x---";
                case "fitToHeight":
                    return "---x" + style.height;
            }
        }

    </script>


</div>
