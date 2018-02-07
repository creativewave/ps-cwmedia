<?php

class HelperMediaUploader extends HelperImageUploader
{
    /**
     * @var int
     */
    protected $min_width = 0;

    /**
     * Set minimum width.
     */
    public function setMinWidth(int $width): self
    {
        $this->min_width = $width;

        return $this;
    }

    /**
     * Get minimum width.
     */
    public function getMinWidth(): int
    {
        return $this->min_width;
    }

    /**
     * Get upload.
     */
    public function getUpload(): array
    {
        return $this->getUploads()[0] ?? [];
    }

    /**
     * Get uploads.
     */
    public function getUploads(): array
    {
        return array_filter($this->process(), [$this, 'isNotEmptyUpload']);
    }

    /**
     * Wether or not media has minimum width.
     */
    protected function hasFileMinWidth(array $file): bool
    {
        return getimagesize($file['tmp_name'])[0] >= $this->getMinWidth();
    }

    /**
     * Validate media width.
     *
     * @see UploaderCore::validate()
     */
    protected function validate(&$file)
    {
        if (!parent::validate($file)) {
            return false;
        }
        if (!$this->hasFileMinWidth($file)) {
            $file['error'] = Tools::displayError(
                sprintf('Media file width should be greater than %d pixels.', $this->getMinWidth())
            );

            return false;
        }

        return true;
    }

    /**
     * Wether or not uploaded media is not empty.
     */
    protected function isNotEmptyUpload(array $file): bool
    {
        return 0 < $file['size'];
    }
}
