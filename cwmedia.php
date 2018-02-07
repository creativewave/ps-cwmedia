<?php

require_once _PS_ROOT_DIR_.'/vendor/autoload.php';

class CWMedia extends Module
{
    /**
     * Registered hooks.
     *
     * @var array
     */
    const HOOKS = [
        'actionAdminControllerSetMedia',
        'actionAdminProductsControllerDuplicateAfter',
        'actionProductAdd',
        'actionProductDelete',
        'actionProductUpdate',
        'displayAdminProductsExtra',
        'displayHeader',
        'displayProductMedia',
    ];

    /**
     * Installed models.
     *
     * @var array
     */
    const MODELS = ['ProductMedia'];

    /**
     * Installed tabs.
     *
     * @var array
     */
    const TABS = [
        [
            'name'       => 'Media',
            'class_name' => 'AdminCWMedia',
            'module'     => 'cwmedia',
            'id_parent'  => 9, // Catalog menu
        ],
    ];

    /**
     * @see ModuleCore
     */
    public $name    = 'cwmedia';
    public $tab     = 'front_office_features';
    public $version = '1.0.0';
    public $author  = 'Creative Wave';
    public $need_instance = 0;
    public $ps_versions_compliancy = [
        'min' => '1.6',
        'max' => '1.6.99.99',
    ];

    /**
     * Initialize module.
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName      = $this->l('Media');
        $this->description      = $this->l('Display product media.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module.
     */
    public function install(): bool
    {
        return parent::install()
               and $this->addDirectory($this->getMediaPath())
               and $this->addHooks(static::HOOKS)
               and $this->addModels(static::MODELS)
               and $this->addTabs(static::TABS);
    }

    /**
     * Uninstall module.
     */
    public function uninstall(): bool
    {
        $this->_clearCache('*');

        return parent::uninstall()
               and $this->removeDirectory($this->getMediaPath())
               and $this->removeModels(static::MODELS)
               and $this->removeTabs(static::TABS);
    }

    /**
     * Add CSS and JS on admin product page.
     */
    public function hookActionAdminControllerSetMedia(array $params)
    {
        if ($this->isPageAdminProduct()) {
            $this->context->controller->addCSS($this->_path.'css/admin-product-tab.css');
            $this->context->controller->addJS($this->_path.'js/admin-product-tab.js');
        }
    }

    /**
     * Display an extra tab on admin product page.
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $template_name = 'admin-products-extra.tpl';
        $id_cache = $this->getCacheId();

        if (!$this->isCached($template_name, $id_cache)) {
            $id_product = $this->getValue('id_product');
            $id_shop = $this->getContextShopId();
            $json = $this->getJson([
                'add'        => $this->l('Add media', 'js'),
                'entrypoint' => $this->getControllerLink('AdminCWMedia'),
                'library'    => [
                    'error' => $this->l('Could not load media list.', 'js'),
                    'title' => $this->l('Select media', 'js'),
                ],
                'link'       => [
                    'error' => $this->l('Could not find an identifier in URL.', 'js'),
                    'title' => $this->l('Please fill in a Youtube URL', 'js'),
                ],
            ]);
            $this->setTemplateVars([
                'hints'    => $this->getMediaUploaderHints(),
                'json'     => $json,
                'media'    => $this->getProductMedia($id_product, $id_shop),
                'tab_name' => $this->getAdminProductTabName(),
                'uploader' => $this->getMediaUploader()->render(),
            ]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Add media and set product media.
     */
    public function hookActionProductAdd(array $params)
    {
        // See CWMedia::setProductMedia()
        // See CWMedia::hookActionAdminProductsControllerDuplicateAfter()
        $this->new_id_product = $params['id_product'];

        if (!$this->shouldSaveMedia()) {
            return;
        }

        $this->addMediaAndSetProductMedia($params['id_product']);
    }

    /**
     * Add media and set product media.
     *
     * @todo Submit PR to fix $_GET['addproduct'] when adding a product, then
     * clean this up.
     */
    public function hookActionProductUpdate(array $params)
    {
        if (!$this->shouldSaveMedia()) {
            return;
        }
        // TODO: remove this condition after doing @todo.
        if ($this->isMultistoreContext() and !$this->isFieldSubmitted($this->name)) {
            return;
        }
        $this->addMediaAndSetProductMedia($params['id_product']);
    }

    /**
     * Remove deleted product media.
     * This hook may never have to do anything, as products delete operations in
     * database should cascade automatically on each product media table rows.
     */
    public function hookActionProductDelete(array $params)
    {
        $ids_shops = $this->getContextShopsIds();
        $this->removeProductMedia($params['id_product'], $ids_shops)
        or $this->addContextError($this->l('An error occurred while attempting to delete media.'));
    }

    /**
     * Set duplicated product media.
     *
     * @todo Duplicate product media shop by shop instead of using product media
     * from the context shop.
     */
    public function hookActionAdminProductsControllerDuplicateAfter(array $params)
    {
        $old_id_product = $this->getValue('id_product');
        $ids_shops = $this->getContextShopsIds();
        $this->duplicateProductMedia($this->new_id_product, $old_id_product, $ids_shops)
        or $this->addContextError($this->l('An error occurred while attempting to duplicate media.'));
    }

    /**
     * Add CSS and JS on public product page.
     */
    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isPagePublicProduct()) {
            return '';
        }
        $this->context->controller->addCSS(__DIR__.'/css/media.css');
        $this->context->controller->addJS(__DIR__.'/js/media.js');

        return '';
    }

    /**
     * Display product media.
     */
    public function hookDisplayProductMedia(array $params): string
    {
        $template_name = 'media.tpl';
        $id_cache = $this->getCacheId();

        if (!$this->isCached($template_name, $id_cache)) {
            $id_product = $this->getValue('id_product');
            $id_shop = $this->getContextShopId();
            $this->setTemplateVars(['media' => $this->getProductMedia($id_product, $id_shop)]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Add directory.
     */
    protected function addDirectory(string $path): bool
    {
        return mkdir($path, 0775);
    }

    /**
     * Add hooks.
     */
    protected function addHooks(array $hooks): bool
    {
        return array_product(array_map([$this, 'registerHook'], $hooks));
    }

    /**
     * Add models.
     */
    protected function addModels(array $models): bool
    {
        return array_product(array_map([$this, 'addModel'], $models));
    }

    /**
     * Add model.
     */
    protected function addModel(string $model): bool
    {
        return (new CW\ObjectModel\Extension(new $model(), $this->getDb()))->install();
    }

    /**
     * Add tabs.
     */
    protected function addTabs(array $tabs): bool
    {
        return array_product(array_map([$this, 'addTab'], $tabs));
    }

    /**
     * Add tab.
     */
    protected function addTab(array $tab): bool
    {
        $object = new Tab();
        foreach ($tab as $prop => $value) {
            $object->$prop = 'name' === $prop
                ? [$this->getContextShopDefaultLanguageId() => $value]
                : $value;
        }

        return $object->add();
    }

    /**
     * Remove directory.
     */
    protected function removeDirectory(string $path): bool
    {
        return Tools::deleteDirectory($path) or true;
    }

    /**
     * Remove models.
     */
    protected function removeModels(array $models): bool
    {
        return array_product(array_map([$this, 'removeModel'], $models));
    }

    /**
     * Remove model.
     */
    protected function removeModel(string $model): bool
    {
        return (new CW\ObjectModel\Extension(new $model(), $this->getDb()))->uninstall();
    }

    /**
     * Remove tabs.
     */
    protected function removeTabs(array $tabs): bool
    {
        return array_product(array_map([$this, 'removeTab'], $tabs));
    }

    /**
     * Remove tab.
     */
    protected function removeTab(array $tab): bool
    {
        $id_tab = Tab::getIdFromClassName($tab['class_name']);

        return (new Tab($id_tab))->delete();
    }

    /**
     * Add media and set product media.
     */
    protected function addMediaAndSetProductMedia(int $id_product)
    {
        $media = $this->getMediaValues();
        $error = $this->l('An error occurred while attempting to save media.');

        // Add media.
        foreach ($media as &$m) { // Set `$m[id_media]` by using a reference of `$m`.
            if ($this->isNewMediaValue($m) and !$this->addMedia($m)) {
                return $this->addContextError($error);
            }
        }

        // Set product media.
        $ids_shops = $this->getContextShopsIds();
        $this->setProductMedia($id_product, $media, $ids_shops) or $this->addContextError($error);
    }

    /**
     * Set product media.
     */
    protected function setProductMedia(int $id_product, array $media, array $ids_shops): bool
    {
        // Add new product media associations and/or update their positions.
        foreach ($media as $position => $prop) {
            $m = $this->getMedia($prop['id_media']);
            $product = ['id_product' => $id_product, 'position' => $position + 1];
            if (!$m->addProduct($product, $ids_shops)) {
                return false;
            }
        }
        // There is nothing more to do if product is new (avoid extra request).
        if (!empty($this->new_id_product)) {
            return true;
        }
        // Remove old product media associations.
        foreach ($ids_shops as $id_shop) {
            $ids_old_media = array_column($this->getProductMedia($id_product, $id_shop), 'id_media');
            $ids_del_media = array_diff($ids_old_media, array_column($media, 'id_media'));
            foreach ($ids_del_media as $id_media) {
                $m = $this->getMedia($id_media);
                if (!$m->removeProduct($id_product, [$id_shop])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Duplicate product media.
     */
    protected function duplicateProductMedia(int $new_id_product, int $old_id_product, array $ids_shops): bool
    {
        $ids_media = $this->getProductMediaIds($old_id_product);
        foreach ($ids_media as $position => $id_media) {
            $media = $this->getMedia($id_media);
            $product = ['id_product' => $new_id_product, 'position' => $position + 1];
            if (!$media->addProduct($product, $ids_shops)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove product media.
     */
    protected function removeProductMedia(int $id_product, array $ids_shops): bool
    {
        $ids_media = $this->getProductMediaIds($id_product);
        foreach ($ids_media as $id_media) {
            if (!$this->getMedia($id_media)->removeProduct($id_product, $ids_shops)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get product media.
     */
    protected function getProductMedia(int $id_product, int $id_shop): array
    {
        return ProductMedia::getProductMedia($id_product, $id_shop);
    }

    /**
     * Get product media IDs.
     */
    protected function getProductMediaIds(int $id_product): array
    {
        return ProductMedia::getProductMediaIds($id_product);
    }

    /**
     * Add media.
     */
    protected function addMedia(array &$fields): bool
    {
        if (!empty($fields['error'])) {
            return false;
        }

        $media = $this->getMedia();
        $media->id_type = $this->getMediaTypeId($fields['type']);
        $media->content = $fields['content'] ?? $fields['name'];
        if ('video/youtube' === $fields['type']) {
            $source = "https://img.youtube.com/vi/$media->content/0.jpg";
            $fields['save_path'] = $this->getMediaUploader()->getFilePath();
            if (!$this->duplicateFile($source, $fields['save_path'])) {
                return false;
            }
        }
        $media->file   = $fields['save_path'];
        list($width, $height) = getimagesize($media->file);
        $media->width  = $width;
        $media->height = $height;

        return $media->save() and $fields['id_media'] = $media->id;
    }

    /**
     * Get media.
     */
    protected function getMedia(int $id = 0): ProductMedia
    {
        return new ProductMedia($id);
    }

    /**
     * Get media path.
     */
    protected function getMediaPath(): string
    {
        return _PS_IMG_DIR_."$this->name/";
    }

    /**
     * Get media type ID.
     */
    protected function getMediaTypeId(string $value): int
    {
        return array_search($value, ProductMedia::TYPES);
    }

    /**
     * Get media uploader.
     */
    protected function getMediaUploader(): HelperMediaUploader
    {
        static $instance;

        return $instance ?? $instance = (new HelperMediaUploader($this->name))
            ->setMinWidth(min(ProductMedia::SIZES))
            ->setAcceptTypes(ProductMedia::EXTENSIONS)
            ->setMultiple(!$this->isUserOnSafariForWindows());
    }

    /**
     * Get media uploader hints.
     */
    protected function getMediaUploaderHints(): string
    {
        $hints = [];
        $formats = implode(', ', $this->getMediaUploader()->getAcceptTypes());
        $hints['format'] = sprintf('Formats %s, are allowed.', $formats);
        $width = $this->getMediaUploader()->getMinWidth();
        $hints['width'] = sprintf('A minimum width of %s px is required.', $width);

        return implode(' ', $hints);
    }

    /**
     * Get media values from $_POST/$_FILES.
     */
    protected function getMediaValues(): array
    {
        $existing_media = $this->getValues('media');
        $uploaded_media = $this->getMediaUploader()->getUploads();

        return array_merge($existing_media, $uploaded_media);
    }

    /**
     * Add context error.
     */
    protected function addContextError(string $message): string
    {
        return $this->context->controller->errors[] = $message;
    }

    /**
     * Duplicate file.
     */
    protected function duplicateFile(string $source, string $destination): bool
    {
        return Tools::copy($source, $destination);
    }

    /**
     * Get admin product tab name.
     */
    protected function getAdminProductTabName(): string
    {
        return 'Module'.ucfirst($this->name);
    }

    /**
     * Get context shop default language ID.
     */
    protected function getContextShopDefaultLanguageId(): int
    {
        return Configuration::get('PS_LANG_DEFAULT');
    }

    /**
     * Get context shop ID.
     */
    protected function getContextShopId(): int
    {
        return $this->context->shop->id;
    }

    /**
     * Get context shops IDs.
     */
    protected function getContextShopsIds(): array
    {
        return Shop::getContextListShopID();
    }

    /**
     * Get controller link.
     */
    protected function getControllerLink(string $name): string
    {
        return $this->context->link->getAdminLink('AdminCWMedia');
    }

    /**
     * Get admin controller name.
     */
    protected function getControllerAdminName(): string
    {
        return $this->context->controller->controller_name;
    }

    /**
     * Get public controller name.
     */
    protected function getControllerPublicName(): string
    {
        return Dispatcher::getInstance()->getController();
    }

    /**
     * Get Db.
     */
    protected function getDb(bool $slave = false): Db
    {
        return Db::getInstance($slave ? _PS_USE_SQL_SLAVE_ : $slave);
    }

    /**
     * Get data in JSON format.
     */
    protected function getJson(array $data): string
    {
        return Tools::jsonEncode($data);
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
     * Wether or not field name has been submitted.
     */
    protected function isFieldSubmitted(string $name): bool
    {
        return $this->context->controller->checkMultishopBox($name);
    }

    /**
     * Wether or not context is multistore.
     */
    protected function isMultistoreContext(): bool
    {
        return Shop::isFeatureActive() and Shop::CONTEXT_SHOP !== Shop::getContext();
    }

    /**
     * Wether or not a media value from $_GET/$_POST/$_FILES is new.
     */
    protected function isNewMediaValue(array $media): bool
    {
        return empty($media['id_media']);
    }

    /**
     * Wether or not admin product page is currently loading.
     */
    protected function isPageAdminProduct(): bool
    {
        return 'AdminProducts' === $this->getControllerAdminName()
               and ($this->isSetKey('addproduct') or $this->isSetKey('updateproduct'));
    }

    /**
     * Wether or not public product page is currently loading.
     */
    protected function isPagePublicProduct(): bool
    {
        return 'product' === $this->getControllerPublicName();
    }

    /**
     * Wether or not a key is set in $_GET/$_POST.
     */
    protected function isSetKey(string $key): bool
    {
        return Tools::getIsset($key);
    }

    /**
     * Wether or not tab name is submitted.
     */
    protected function isTabSubmitted(string $name): bool
    {
        $submitted_tabs = $this->getValues('submitted_tabs');

        return in_array($name, $submitted_tabs, true);
    }

    /**
     * Wether or not current user is browing with Safari for Windows.
     */
    protected function isUserOnSafariForWindows(): bool
    {
        return 'Apple Safari' === Tools::getUserBrowser()
               and 'Windows' === Tools::getUserPlatform();
    }

    /**
     * Set template variables.
     */
    protected function setTemplateVars(array $vars): Smarty_Internal_Data
    {
        return $this->smarty->assign($vars);
    }

    /**
     * Wether or not media should be saved.
     *
     * @todo Submit PR to set $_GET['addproduct'] when adding a product, instead
     * of $_GET['updateproduct'], then clean this up.
     */
    protected function shouldSaveMedia(): bool
    {
        // if ($this->getValue('updateproduct') and $this->isMultistoreContext() and !$this->isFieldSubmitted($this->name)) {
        //     return false;
        // }

        return !$this->getValue('ajax') and $this->isTabSubmitted($this->getAdminProductTabName());
    }
}
