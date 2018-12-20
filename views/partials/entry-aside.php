@if($app->module('cockpit')->hasaccess('imagestyles', 'rebuild'))
<div class="uk-margin-top-large" if="{entry._id && entry._mby && showStylesAction}">
  <div class="uk-margin">
    <a class="uk-button uk-text-danger" data-uk-tooltip="pos:'bottom'" onclick="{() => rebuildStyles(collection.name, entry)}" title="Delete all content image styles.">
        <i class="uk-icon-trash-o uk-margin-small-right"></i>@lang('Image Styles')
    </a>
  </div>

  <script>
    var $this = this;
    this.showStylesAction = false;

    this.on('mount', function() {

      window.setTimeout(function() {
        App.callmodule('imagestyles:hasStyles', [$this.collection]).then(function(data) {
          if (data.result) {
            $this.showStylesAction = true;
            $this.update();
          }
        });
      }, 50);

      $this.update();
    });

    function rebuildStyles(name, entry) {
      App.callmodule('imagestyles:deleteEntryStyles', [name, entry]).then(function() {
         App.ui.notify(App.i18n.get("All Image styles for collection deleted with success! Save to generate them again."), "success");
      });
      return false;
    }
  </script>

</div>
@endif
