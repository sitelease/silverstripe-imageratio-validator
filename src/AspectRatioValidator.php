<?php

namespace Sitelease\AspectRatioValidator;

use SilverStripe\Assets\Upload_Validator;
use SilverStripe\MimeValidator\MimeUploadValidator;
use SilverStripe\Core\Config\Configurable;
use Sitelease\AspectRatioValidator\AspectRatioValidatorFailedException;

class AspectRatioValidator extends MimeUploadValidator
{
    use Configurable;

    /**
     * Stores the aspect ratios that an image
     * can have to be considered valid
     *
     * @var array
     */
    protected array $allowedAspectRatios = [];

    /**
     * Returns an array of valid aspect ratios
     *
     * @return array
     */
    public function getAllowedAspectRatios(): array
    {
        return $this->allowedAspectRatios;
    }

    /**
     * Set the array of valid aspect ratios
     *
     * @return self
     */
    public function setAllowedAspectRatios(array $ratios)
    {
        $this->allowedAspectRatios = $ratios;
        return $this;
    }

    public function __construct(?Upload_Validator $validator)
    {
        if ($validator) {
            $this->setAllowedExtensions($validator->getAllowedExtensions());
            $this->setAllowedMaxFileSize($validator->allowedMaxFileSize);
        }
    }

    /**
     * Returns an array containing information about the file
     * (isImage => bool, width => int|null, and height => int|null)
     *
     * @param string $filename
     * @return array
     */
    public function getFileData(string $filename): array
    {
        $imageDetails = getimagesize($filename);

        if ($imageDetails === false) {
            return [
                'isImage' => false,
                'width'   => null,
                'height'  => null,
            ];
        } else {
            return [
                'isImage' => true,
                'width'   => $imageDetails[0],
                'height'  => $imageDetails[1],
            ];
        }
    }

    /**
     * Returns true if the uploaded image has a valid aspect ratio.
     * Otherwise throws a validation error
     *
     * @return bool
     * @throws AspectRatioValidatorFailedException
     */
    public function aspectRatioIsValid(): bool
    {
        $allowedAspectRatios = $this->allowedAspectRatios;
        $imageDetails = $this->getFileData($this->tmpFile['tmp_name']);

        if ($imageDetails['isImage'] === false) {
            throw new AspectRatioValidatorFailedException(
                _t(__CLASS__.'.NotAnImage', 'File is not an image')
            );
        }

        $ratioIsValid = false;
        $imageRatioDecimal = number_format($imageDetails['width'] / $imageDetails['height'], 2);
        foreach ($allowedAspectRatios as $ratio) {
            $ratioParts = explode("x", $ratio);
            $ratioDecimal = number_format($ratioParts[0] / $ratioParts[1], 2);

            if ($imageRatioDecimal === $ratioDecimal) {
                $ratioIsValid = true;
                break;
            }
        }

        if (!$ratioIsValid) {
            throw new AspectRatioValidatorFailedException(
                _t(__CLASS__ . '.InvalidAspectRatio', 'Image aspect ratio invalid (must be: {validRatios})', [
                    'validRatios' => join(', ', $allowedAspectRatios)
                ])
            );
        }

        return true;
    }

    /**
     * Returns true if the uploaded image or file
     * has a valid aspect ratio
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (parent::validate() === false) {
            return false;
        }

        try {
            return $this->aspectRatioIsValid();
        } catch (AspectRatioValidatorFailedException $e) {
            $this->errors[] = _t(__CLASS__.'.ErrorPrefix', 'Error: {error}', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
