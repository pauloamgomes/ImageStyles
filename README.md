# Cockpit Image Styles

This addon extends Cockpit CMS core functionality by introducing the possibility to define image styles that can be assigned to image fields.
Cockpit provides a simple mechanism to transform images where its possible by invoking the endpoint to apply a set of transformations (e.g resize, blur, etc..) to an existing image. However, using such mechanism can be painful since it requires for each image a new request.

Taking into consideration similar concepts from other CMS's, where its possible to encapsulate those transformations in a single entity and therefore apply it automatically to all images present in a collection, this addon provides:

* Admin interface to configure the Image Styles
* REST endpoint to apply the image style to an image (e.g. ```/api/imagestyles/style/Banner?token=XX&src=storage/uploads/image.jpg```)
* Generation of all image styles when saving a collection
* No 3rd party dependencies, everything is based on the Cockpit API

The Cockpit Action will transform the Cockpit collections API response by injecting a "styles" attribute in the image fields:

```json
[
    {
        "name": "My Collection Entry",
        "image": {
            "path": "/storage/uploads/2018/01/31/5a71198012e6fimage12.png",
            "styles": [
                {
                    "style": "Banner",
                    "path": "/storage/thumbs/46dcaf8ebdcf761ff954a71e25114480_800x200_90_1523051274_thumbnail_b28354b543375bfa94dabaeda722927f.png"
                },
                {
                    "style": "Square",
                    "path": "/storage/thumbs/3d4aa297d753af28f8bceedf8bc77098_200x200_90_1523051274_resize_adb115059e28d960fa8badfac5516667.png"
                }
            ]
        },
        "_modified": 1523221256,
    }
]
```

Since version 1.7 and due performance concerns the image styles are not generated anymore during the collections.find.after hook, and instead when the collection is saved.
This removes the risks of impacting the performance of the Cockpit API for fetching collections.
The previous example JSON is replaced as below:

```json
[
    {
        "name": "My Collection Entry",
        "image": {
            "path": "/storage/uploads/2018/01/31/5a71198012e6fimage12.png",
            "cimgt": 1540853512,
            "styles": [
                {
                    "style": "Banner",
                    "path": "/styles/page/46dcaf8ebdcf761ff954a71e25114480_800x200_90_1523051274_thumbnail_b28354b543375bfa94dabaeda722927f.png?cimgt=1540853512"
                },
                {
                    "style": "Square",
                    "path": "/styles/page/3d4aa297d753af28f8bceedf8bc77098_200x200_90_1523051274_resize_adb115059e28d960fa8badfac5516667.png?cimgt=1540853512"
                }
            ]
        },
        "_modified": 1523221256,
    }
]
```

The images are now stored on the storage/styles/<collection-name> folder instead of the storage/thumbs, so they are not removed when clearing the Cockpit cache.
A query string (cimgt) based on the modification time is used as a simple cache burst mechanism. It's possible force the rebuild of the image styles for a collection when editing the collection.

## Installation

1. Confirm that you have Cockpit CMS (Next branch) installed and working.
2. Download zip and extract to 'your-cockpit-docroot/addons' (The extracted folder name must be renamed to ImageStyles, e.g. cockpitcms/addons/**ImageStyles**)
3. Access module settings (http://your-cockpit-site/image-styles) and confirm that page loads.

## Configuration

The Addon doesn't require any extra configuration.
When enabled, it will be available to the admin with all features.

### Permissions

A set of permissions that can be configured per role:

- manage.view - can view image styles
- manage.admin - can create, edit and delete image styles
- rebuild - can rebuild the generated styles for a collection

### Support for CloudStorage

Since version 1.9 cloud storage is supported via the CloudStorage addon - https://github.com/agentejo/CloudStorage.

If you want your styles saved on a cloud provider like s3 add in config/config.yaml:

```yaml
cloudstorage:
  styles:
    type: s3
    key: <KEY>
    secret: <SECRET>
    region: <REGION>
    bucket: <BUCKET-NAME>
    prefix: styles
```

## Supported fields

The Addon supports the below field types:

- Image
- Asset
- Gallery

Above fields can be used in nested fields like:

- Layout (since version 1.6)
- Set
- Repeater


## Custom fields

Custom fields can be supported by implementing the action `imagestyles.fields` and providing your field structure there, e.g.:

```php
$app->on('imagestyles.fields', function ($collection, &$fields) {
  foreach ($collection['fields'] as $field) {
    if ($field['type'] === 'metatags') {
      $defaultStyles = ['Style1', 'Style2'];
      if (!empty($field['options'] && !empty($field['options']['image']))) {
        $styles = $field['options']['image']['styles'] ?? $defaultStyles;
      }
      $field['options']['image']['styles'] = $styles;
      $fields[$field['name']] = $field;
      break;
    }
  }
});
```

When configuring each field its just required to set a "styles" attribute as below:

### Set field example
```json
{
  "fields": [
    {
      "name": "MyImage",
      "type": "image",
      "styles": [
        "SimpleBlock",
        "Square"
      ]
    },
    {
      "name": "description",
      "type": "text"
    }
  ]
}
```

### Repeater field example
```json
{
  "field": {
    "type": "image",
    "label": "Image",
    "styles": [
      "SimpleBlock"
    ]
  }
}
```

### Gallery field example
```json
{
  "styles": [
    "SimpleBlock"
  ]
}
```

## Usage

The first step to use the Addon requires the creation of a new image style, that can be observed in the following screencast:

![Screencast](https://api.monosnap.com/rpc/file/download?id=y9gkZp50ED7PEk06zjfD1YCA43BBZ1)

Using the REST API its possible to apply the created style to any existing image and receive the image URL, Base64 or binary format:

A typical image style will return just the URL:

```
curl "http://cockpit.docker.localhost/api/imagestyles/style/Banner?token=XXXXXXX&src=storage/uploads/image.jpg"
```

![REST API Request](https://monosnap.com/file/tpHX5UNDHirnOGDxExENWAxkcieml3.png)

The image style can be configured to return a Base64 string by default (or that can be passed in the request params):

```
curl "http://cockpit.docker.localhost/api/imagestyles/style/Banner?token=XXXXXXX&src=storage/uploads/image.jpg&base64=1"
```

![REST API Request with base64](https://monosnap.com/file/D3dULBgZ7RZK9JrvZi3CCzrOx0nfJa.png)

The output request parameter can be used to receive the image instead of a URL:

```
curl "http://cockpit.docker.localhost/api/imagestyles/style/Banner?token=XXXXXXX&src=storage/uploads/image.jpg&output=1" > image.jpg
```

![REST API Request with output](https://monosnap.com/file/69591aVZYH64NPG1PebCKeDiu2VmZj.png)

For configuring an image field to have one or more styles automatically added it's only required to edit the field settings and add a "styles" attribute. When retrieving a collection that includes that image field, the corresponding image style URLs will be incorporated in the response:

![Image Style with Field](https://api.monosnap.com/rpc/file/download?id=GtxS0KcGjeEvNuMCWUnujDHK482lmQ)

## Copyright and license

Copyright since 2018 pauloamgomes under the MIT license.


