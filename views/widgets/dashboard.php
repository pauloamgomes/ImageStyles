<div>

    <div class="uk-panel-box uk-panel-card">

        <div class="uk-panel-box-header uk-flex">
            <strong class="uk-panel-box-header-title uk-flex-item-1">
                @lang('Image Styles')

                @hasaccess?('imagestyles', 'create')
                <a href="@route('/image-styles/style')" class="uk-icon-plus uk-margin-small-left" title="@lang('Create Image Style')" data-uk-tooltip></a>
                @end
            </strong>

            @if(count($imagestyles))
            <span class="uk-badge uk-flex uk-flex-middle"><span>{{ count($imagestyles) }}</span></span>
            @endif
        </div>

        @if(count($imagestyles))

            <div class="uk-margin">

                <ul class="uk-list uk-list-space uk-margin-top">
                    @foreach(array_slice($imagestyles, 0, count($imagestyles) > 5 ? 5: count($imagestyles)) as $imagestyle)
                    <li>
                        <a href="@route('/image-styles/style/'.$imagestyle['name'])">

                            <img class="uk-margin-small-right uk-svg-adjust" src="@url(isset($imagestyle['icon']) && $imagestyle['icon'] ? 'assets:app/media/icons/'.$imagestyle['icon']:'imagestyles:icon.svg')" width="18px" alt="icon" data-uk-svg>

                            {{ @$imagestyle['label'] ? $imagestyle['label'] : $imagestyle['name'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>

            </div>

            <div class="uk-panel-box-footer">
                <a href="@route('/image-styles')">@lang('See all')</a>
            </div>

        @else

            <div class="uk-margin uk-text-center uk-text-muted">

                <p>
                    <i class="uk-icon-justify uk-icon-image" style="font-size: 25px"></i>
                </p>

                @lang('No Image Styles').

                @hasaccess?('imagestyles', 'manage.admin')
                <a href="@route('/image-styles/create')">@lang('Create new')</a>.
                @end

            </div>

        @endif

    </div>

</div>
