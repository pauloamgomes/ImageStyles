<cp-effectsmanager>

    <div ref="effectscontainer" class="uk-sortable uk-grid uk-grid-small uk-grid-gutter uk-form">

        <div class="uk-width-{effect.width}" data-idx="{idx}" each="{ effect,idx in effects }">

            <div class="uk-panel uk-panel-box uk-panel-card">

                <div class="uk-grid uk-grid-small">

                    <div class="uk-width-1-4">
                        <div>
                            <input class="uk-form-small uk-form-blank" readonly value="{ getEffectLabel(effects[idx].type) }">
                        </div>
                    </div>


                    <div class="uk-flex-item-1 uk-flex">
                        <span class="uk-flex-item-1 uk-form-small uk-form-blank">{ JSON.stringify(effects[idx].options) }</span>
                    </div>

                    <div class="uk-text-right">

                        <ul class="uk-subnav">

                            <li>
                                <a onclick="{ parent.effectSettings }"><i class="uk-icon-cog uk-text-primary"></i></a>
                            </li>

                            <li>
                                <a class="uk-text-danger" onclick="{ parent.removeEffect }">
                                    <i class="uk-icon-trash"></i>
                                </a>
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="uk-modal uk-sortable-nodrag" ref="modalField">
        <div class="uk-modal-dialog" if="{effect}">

            <div class="uk-form-row uk-text-large uk-text-bold">
                { 'Effect' }
            </div>

            <div class="uk-margin-top ref-tab">
                <div>
                    <div class="uk-form-row">

                        <label class="uk-text-muted uk-text-small">{ App.i18n.get('Type') }:</label>
                        <div class="uk-form-select uk-width-1-1 uk-margin-small-top">
                            <a class="uk-text-capitalize">{ effect.type }</a>
                            <select class="uk-width-1-1 uk-text-capitalize" bind="effect.type" onchange="{ resetEffect }">
                                <option each="{type,typeidx in effecttypes}" value="{type.type}">{type.label}</option>
                            </select>
                        </div>
                    </div>

                    <div class="uk-form-row">
                        <label class="uk-text-small uk-text-bold uk-margin-small-bottom">{ App.i18n.get('Options') } <span class="uk-text-muted">JSON</span></label>
                        <field-object cls="uk-width-1-1" bind="effect.options" rows="4" height="150"></field-object>
                    </div>
                </div>
            </div>

            <div class="uk-modal-footer uk-text-right"><button class="uk-button uk-button-large uk-button-link uk-modal-close">{ App.i18n.get('Close') }</button></div>

        </div>
    </div>

    <div class="uk-margin-top" show="{effects.length}">
        <a class="uk-button uk-button-outline uk-text-primary" onclick="{ addEffect }"><i class="uk-icon-plus-circle"></i> { App.i18n.get('Add effect') }</a>
    </div>

    <div class="uk-width-medium-1-3 uk-viewport-height-1-4 uk-container-center uk-text-center uk-flex uk-flex-middle" if="{ !effects.length && !reorder }">

        <div class="uk-animation-fade">

            <p class="uk-text-xlarge">
                <img riot-src="{ App.base('/assets/app/media/icons/form-editor.svg') }" width="48" height="48" >
            </p>

            <hr>

            { App.i18n.get('No effects added yet') }.
            <span data-uk-dropdown="pos:'bottom-center'">
                <a onclick="{ addEffect }">{ App.i18n.get('Add effect') }.</a>
            <span>

        </div>

    </div>


    <script>

        riot.util.bind(this);

        var $this = this;

        this.effects  = [];
        this.effect = null;
        this.reorder = false;

        // set all available effects
        this.effecttypes = [
            { type: 'blur', label: 'Blur', options: { value: '5' } },
            { type: 'brighten', label: 'Brighten', options: { value: '20' } },
            { type: 'colorize', label: 'Colorize', options: { value: 'LightSkyBlue'} },
            { type: 'contrast', label: 'Contrast', options: { value: '-10' } },
            { type: 'darken', label: 'Darken', options: { value: '20' } },
            { type: 'desaturate', label: 'Desaturate', options: {} },
            { type: 'edge detect', label: 'Edge Detect', options: {} },
            { type: 'emboss', label: 'Emboss', options: {} },
            { type: 'flip', label: 'Flip', options: { value: 'x' } },
            { type: 'invert', label: 'Invert', options: {} },
            { type: 'opacity', label: 'Opacity', options: { value: '0' } },
            { type: 'pixelate', label: 'Pixelate', options: { value: '10' } },
            { type: 'sepia', label: 'Sepia', options: {} },
            { type: 'sharpen', label: 'Sharpen', options: {} },
            { type: 'sketch', label: 'Sketch', options: {} },
        ];

        this.$updateValue = function(value, effect) {

            if (!Array.isArray(value)) {
                value = [];
            }

            if (this.effects !== value) {

                this.effects = value;

                this.effects.forEach(function(effect) {
                    if (Array.isArray(effect.options)) {
                        effect.options = {};
                    }
                });

                this.update();
            }

        }.bind(this);

        this.on('bindingupdated', function(){
            $this.$setValue(this.effects);
        });

        this.one('mount', function(){

            UIkit.sortable(this.refs.effectscontainer, {

                dragCustomClass:'uk-form'

            }).element.on("change.uk.sortable", function(e, sortable, ele) {

                if (App.$(e.target).is(':input')) {
                    return;
                }

                ele = App.$(ele);

                var effects = $this.effects,
                    cidx   = ele.index(),
                    oidx   = ele.data('idx');

                effects.splice(cidx, 0, effects.splice(oidx, 1)[0]);

                // hack to force complete effects rebuild
                App.$($this.refs.effectscontainer).css('height', App.$($this.refs.effectscontainer).height());

                $this.effects = [];
                $this.reorder = true;
                $this.update();

                setTimeout(function() {
                    $this.reorder = false;
                    $this.effects = effects;
                    $this.update();
                    $this.$setValue(effects);

                    setTimeout(function(){
                        $this.refs.effectscontainer.style.height = '';
                    }, 30)
                }, 0);

            });

            App.$(this.root).on('click', '.uk-modal [data-uk-tab] li', function(e) {
                var item = App.$(this),
                    idx = item.index();

                item.closest('.uk-tab')
                    .next('.ref-tab')
                    .children().addClass('uk-hidden').eq(idx).removeClass('uk-hidden')
            });
        });

        addEffect() {

            this.effect = {
                'type'    : 'blur',
                'options' : {
                    'value': '5'
                }
            };

            this.effects.push(this.effect);

            $this.$setValue(this.effects);
            UIkit.modal(this.refs.modalField).show()
        }

        removeEffect(e) {
            this.effects.splice(e.item.idx, 1);
            $this.$setValue(this.effects);
        }

        effectSettings(e) {
            this.effect = e.item.effect;

            UIkit.modal(this.refs.modalField).show()
        }

        getEffectLabel(type) {
            const effect = this.effecttypes.find(function(element) {
                return element.type === type;
            });
            return effect.label;
        }

        resetEffect(e) {
            const effect = this.effecttypes.find(function(element) {
                return element.type === e.currentTarget.value;
            });
            this.effect.options = effect.options;
        }

    </script>

</cp-effectsmanager>
