# Silverstripe image aspect ratio validator

This extends the standard upload validator and provides additional checks on the aspect ratio of an uploaded image.

The validator must be applied directly to an UploadField. By default, image upload fields have their validators altered to only accept image extensions. So we recommend that you pass the old validator as the first parameter when creating the new one so all its settings can be copied over. Like so:

```php
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $defaultValidator = $fields->dataFieldByName('Image')->getValidator();
        $fields->addFieldsToTab('Root.Main', [
            UploadField::create('Image')
            ->setValidator(AspectRatioValidator::create($defaultValidator)->setAllowedAspectRatios(['1x1'])),
        ]);

        return $fields;
    }
```

**Note:** the validator should only be added to upload fields where the underlying class is an `Image` or subclass thereof. Adding the validator to an upload field where files may be uploaded will cause any non-images to be rejected.
