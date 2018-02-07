<?php

class AdminCWMediaController extends ModuleAdminController
{
    /**
     * @see AdminController.
     */
    public $className  = 'ProductMedia';
    public $identifier = 'id_media';
    public $table      = 'cw_media';
    public $bootstrap  = true;

    /**
     * Add JS on edit page.
     *
     * @see AdminController::setMedia()
     */
    public function setMedia()
    {
        if ($this->isPageEdit()) {
            $this->addJs($this->getModulePath().'js/controller.js');
        }

        return parent::setMedia();
    }

    /**
     * Process AJAX request for getting media list.
     */
    public function ajaxProcessGetMediaList(): string
    {
        $excluded = $this->getValues('excluded');
        $page     = $this->getValue('page', 1);
        $limit    = $this->getValue('limit', 10);

        $media = $this->getMediaList($excluded, $page, $limit);

        die($this->getJson(['status' => 'ok', 'text' => $media]));
    }

    /**
     * Set list actions and fields.
     *
     * @see AdminController::renderList()
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = [
            'id_media' => [
                'title'  => $this->l('ID'),
                'align'  => 'center',
                'class'  => 'fixed-width-xs',
                'search' => true,
            ],
            'content'  => [
                'title'           => $this->l('Thumbnail'),
                'callback_object' => $this,
                'callback'        => 'getListFieldContent',
                'name'            => 'content',
            ],
            'id_type'  => [
                'title'           => $this->l('Type'),
                'callback_object' => $this,
                'callback'        => 'getListFieldType',
                'name'            => 'id_type',
                'search'          => true,
            ],
        ];

        return parent::renderList();
    }

    /**
     * Get content list field.
     */
    public function getListFieldContent(string $content, array $media): string
    {
        return $this->getMediaThumbImg($media['id_media'], $media['width'], $media['height'], $content);
    }

    /**
     * Get type list field.
     */
    public function getListFieldType(int $id_type): string
    {
        list($type, $subtype) = explode('/', ProductMedia::TYPES[$id_type]);

        return Tools::ucwords("$type $subtype");
    }

    /**
     * Set form fields.
     *
     * @see AdminController::renderForm()
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Media'),
                'icon'  => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'id_type',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'content',
                ],
                [
                    'type'  => 'text',
                    'name'  => 'link',
                    'label' => $this->l('Youtube video'),
                ],
                [
                    'type'         => 'html',
                    'name'         => 'file',
                    'label'        => $this->l('Image'),
                    'hint'         => $this->getFormFieldFileHint(),
                    'html_content' => $this->getFormFieldFile(),
                ],
                [
                    'type'         => 'html',
                    'name'         => 'thumb',
                    'label'        => $this->l('Thumbnail'),
                    'condition'    => isset($this->object->content),
                    'html_content' => $this->getFormFieldThumb(),
                ],
                [
                    'type'    => 'swap',
                    'name'    => 'products',
                    'label'   => $this->l('Products'),
                    'options' => [
                        'query' => $this->getFormFieldProductsOptionsQuery(),
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'size'    => 20,
                ],
            ],
            'buttons' => [
                [
                    'type'  => 'submit',
                    'title' => $this->l('Save'),
                    'class' => 'pull-right',
                    'icon'  => 'process-icon-save',
                    'name'  => "submitAdd$this->table",
                ],
            ],
            'submit' => [
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        return parent::renderForm();
    }

    /**
     * Get file form field.
     */
    public function getFormFieldFile(): string
    {
        return $this->getMediaUploader()->render();
    }

    /**
     * Get hint for file form field.
     */
    public function getFormFieldFileHint(): string
    {
        $hints = [];
        $formats = implode(', ', $this->getMediaUploader()->getAcceptTypes());
        $hints['format'] = sprintf('Formats %s, are allowed.', $formats);
        $width = $this->getMediaUploader()->getMinWidth();
        $hints['width'] = sprintf('A minimum width of %s px is required.', $width);

        return implode(' ', $hints);
    }

    /**
     * Get thumb form field.
     */
    public function getFormFieldThumb(): string
    {
        if ($this->isPageAdd() or $this->isPageAddWithError()) {
            return '';
        }

        return $this->getMediaThumbImg(
            $this->object->id,
            $this->object->width,
            $this->object->height,
            $this->object->content
        );
    }

    /**
     * Get options query for products form field.
     */
    public function getFormFieldProductsOptionsQuery(): array
    {
        $id_lang   = $this->getContextShopDefaultLanguageId();
        $start     = 0;
        $limit     = 0;
        $order_by  = 'name';
        $order_way = 'ASC';
        $products  = Product::getProducts($id_lang, $start, $limit, $order_by, $order_way);

        return array_map(function ($product) {
            return [
                'id'   => $product['id_product'],
                'name' => "{$product['name']} - {$product['reference']}",
            ];
        }, $products);
    }

    /**
     * Set media products on update page.
     *
     * @see AdminController::loadObject()
     *
     * @param mixed $create_if_new
     */
    protected function loadObject($create_if_new = false)
    {
        $media = parent::loadObject($create_if_new);

        if ($this->isPageUpdate()) {
            $ids_shops = $this->getContextShopsIds();
            $media->products = $media->getProducts($ids_shops);
        }

        return $media;
    }

    /**
     * Set media products from $_POST and file from $_FILES.
     *
     * @see AdminController::copyFromPost()
     *
     * @param mixed $table
     */
    protected function copyFromPost(&$media, $table)
    {
        $old_content = $media->content;
        parent::copyFromPost($media, $table);

        // Set products.
        $selected_products = $this->getValues('products_selected');
        foreach ($selected_products as $position => $id_product) {
            $media->products[] = ['id_product' => $id_product, 'position' => $position + 1];
        }

        // Set file.
        if ($old_content === $media->content) {
            return;
        }
        if ($media->isType('video')) {
            $source = "https://img.youtube.com/vi/$media->content/0.jpg";
            $media->file = $this->getMediaUploader()->getFilePath();
            if (!$this->duplicateFile($source, $media->file)) {
                return $this->errors[] = $this->l('Could not copy video thumbnail from Youtube.');
            }
        } else {
            $upload = $this->getMediaUploader()->getUpload();
            if (!$upload) {
                return $this->errors[] = $this->l('No media file or URL has been provided.');
            }
            if ($upload['error']) {
                return $this->errors[] = $upload['error'];
            }
            $media->file = $upload['save_path'];
        }
        list($width, $height) = getimagesize($media->file);
        $media->width  = $width;
        $media->height = $height;
    }

    /**
     * Duplicate file.
     */
    protected function duplicateFile(string $source, string $destination): bool
    {
        return Tools::copy($source, $destination);
    }

    /**
     * Get context shop default language ID.
     */
    protected function getContextShopDefaultLanguageId(): int
    {
        return Configuration::get('PS_LANG_DEFAULT');
    }

    /**
     * Get context shops IDs.
     */
    protected function getContextShopsIds(): array
    {
        return Shop::getContextListShopID();
    }

    /**
     * Get media uploader.
     */
    protected function getMediaUploader(): HelperMediaUploader
    {
        static $instance;

        return $instance ?? $instance = (new HelperMediaUploader('media'))
            ->setMinWidth(min(ProductMedia::SIZES))
            ->setAcceptTypes(ProductMedia::EXTENSIONS);
    }

    /**
     * Get data in JSON format.
     */
    protected function getJson(array $data): string
    {
        return Tools::jsonEncode($data);
    }

    /**
     * Get media.
     */
    protected function getMedia(int $id = 0): ProductMedia
    {
        return new ProductMedia($id);
    }

    /**
     * Get media thumb `<img>`.
     */
    protected function getMediaThumbImg(int $id_media, int $full_width, int $full_height, string $alt): string
    {
        $nocache = time(true);
        $src    = ProductMedia::getMediaUrl($id_media, 'thumb')."?$nocache";
        $width  = ProductMedia::SIZES['thumb'];
        $height = $full_height * $width / $full_width;

        return "<img src=\"$src\" alt=\"{$alt}\" width=\"$width\" height=\"$height\" class=\"img-thumbnail\">";
    }

    /**
     * Get media list.
     */
    protected function getMediaList(array $excluded, int $page = 1, int $limit = 10): array
    {
        $media = ProductMedia::getMedia($excluded, $page, $limit);
        foreach ($media as &$m) {
            $m['src'] = ProductMedia::getMediaUrl($m['id_media'], 'thumb');
        }

        return $media;
    }

    /**
     * Get module path.
     */
    protected function getModulePath(): string
    {
        return _MODULE_DIR_.$this->module->name.'/';
    }

    /**
     * Get value from $_GET/$_POST.
     */
    protected function getValue(string $key, string $default = ''): string
    {
        return Tools::getValue($key, $default);
    }

    /**
     * Get values from $_GET/$_POST.
     */
    protected function getValues(string $key, array $default = []): array
    {
        $value = Tools::getValue($key, $default);

        return is_string($value) ? explode(',', $value) : $value;
    }

    /**
     * Wether or not add page is currently loading.
     */
    protected function isPageAdd(): bool
    {
        return 'add' === $this->display && $this->isSetKey("add$this->table");
    }

    /**
     * Wether or not add page with error is currently loading.
     */
    protected function isPageAddWithError(): bool
    {
        return 'edit' === $this->display and !$this->getValue($this->identifier);
    }

    /**
     * Wether or not edit (add or update) page is currently loading.
     */
    protected function isPageEdit(): bool
    {
        return in_array($this->display, ['add', 'edit'], true);
    }

    /**
     * Wether or not update page is currently loading.
     */
    protected function isPageUpdate(): bool
    {
        return 'edit' === $this->display
               and $this->isSetKey("update$this->table")
               and $this->getValue($this->identifier);
    }

    /**
     * Wether or not a key is set in $_GET/$_POST.
     */
    protected function isSetKey(string $key): bool
    {
        return Tools::getIsset($key);
    }
}
