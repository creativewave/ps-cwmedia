<?php

class ProductMedia extends ObjectModel
{
    /**
     * Media file extensions.
     *
     * @var array
     */
    const EXTENSIONS = ['jpeg', 'jpg'];

    /**
     * Media sizes (in pixels).
     *
     * @var array
     */
    const SIZES = [
        'thumb'  =>  300,
        'small'  => 1000, // Tablets and old mobiles.
        'medium' => 1400, // Laptops and high dpi mobiles/tablets.
        'large'  => 1920, // Full HD screens.
    ];

    /**
     * Media types.
     *
     * @var array
     */
    const TYPES = ['image/jpg', 'video/youtube'];

    /** @var int */
    public $id_type;
    /** @var string */
    public $content;
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    /** @var array */
    public $products = [];

    /** @var string */
    public $file;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'        => 'cw_media',
        'primary'      => 'id_media',
        'fields'       => [
            'id_type'  => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'content'  => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isGenericName',
                'size'     => 255,
            ],
            'width' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'height' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
        ],
        'associations' => [
            'products' => [
                'type'        => ObjectModel::HAS_MANY,
                'object'      => 'Product',
                'association' => 'cw_media_product',
                'field'       => 'id_product',
                'multishop'   => true, // @see vendor/creativewave/ps-objectmodel-extension.
                'fields'      => [     // @see vendor/creativewave/ps-objectmodel-extension.
                    'position' => [
                        'type'     => ObjectModel::TYPE_INT,
                        'validate' => 'isUnsignedInt',
                        'required' => true,
                    ],
                ],
            ],
        ],
    ];

    /**
     * Wether or not this media exists in database.
     */
    public function isNew(): bool
    {
        return !Validate::isLoadedObject($this);
    }

    /**
     * Add media and its associations.
     *
     * @see ObjectModel::add()
     *
     * @param mixed $auto_date
     * @param mixed $null_values
     */
    public function add($auto_date = true, $null_values = false): bool
    {
        $ids_shops = Shop::getContextListShopID();

        return parent::add($auto_date, $null_values)
               and $this->setFiles()
               and $this->addProducts($this->products, $ids_shops);
    }

    /**
     * Update media and its associations.
     *
     * @see ObjectModel::update()
     *
     * @param mixed $auto_date
     * @param mixed $null_values
     */
    public function update($auto_date = true, $null_values = false): bool
    {
        $ids_shops = Shop::getContextListShopID();

        return parent::update($auto_date, $null_values)
               and $this->setFiles()
               and $this->setProducts($this->products, $ids_shops);
    }

    /**
     * Delete media file(s).
     *
     * @see ObjectModel::delete()
     */
    public function delete(): bool
    {
        if (parent::delete() and $this->hasMultishopEntries()) {
            return true;
        }

        return $this->removeFiles() and $this->removeDirectory();
    }

    /**
     * Set products.
     */
    public function setProducts(array $products, array $ids_shops): bool
    {
        return $this->removeAllProducts($ids_shops)
               and $this->addProducts($products, $ids_shops);
    }

    /**
     * Add products.
     */
    public function addProducts(array $products, array $ids_shops): bool
    {
        foreach ($products as $product) {
            if (!$this->addProduct($product, $ids_shops)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add product.
     */
    public function addProduct(array $product, array $ids_shops): bool
    {
        foreach ($ids_shops as $id_shop) {
            if (!$this->isValidProduct($product)) {
                return false;
            }
            if (!$this->getDb()->insert(
                static::$definition['associations']['products']['association'],
                [
                    'id_media'   => $this->id,
                    'id_shop'    => $id_shop,
                    'id_product' => $product['id_product'],
                    'position'   => $product['position'],
                ],
                /* $null_values = */ false, // (default)
                /* $use_cache   = */ true,  // (default)
                /* $type        = */ Db::ON_DUPLICATE_KEY // Update position.
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove all products.
     */
    public function removeAllProducts(array $ids_shops): bool
    {
        return $this->getDb()->delete(
            static::$definition['associations']['products']['association'],
            "id_media   = $this->id
            AND id_shop IN (".implode(',', $ids_shops).')'
        );
    }

    /**
     * Remove product.
     */
    public function removeProduct(int $id_product, array $ids_shops): bool
    {
        return $this->getDb()->delete(
            static::$definition['associations']['products']['association'],
            "id_media      = $this->id
            AND id_product = $id_product
            AND id_shop    IN (".implode(',', $ids_shops).')'
        );
    }

    /**
     * Get products.
     */
    public function getProducts(array $ids_shops): array
    {
        return array_column($this->getDb()->executeS($this->getDbQuery()
            ->select('id_product')
            ->from(static::$definition['associations']['products']['association'])
            ->where("id_media = $this->id")
            ->where('id_shop IN ('.implode(',', $ids_shops).')')
            ->groupBy('id_product')
        ), 'id_product');
    }

    /**
     * Wether or not this has a corresponding type.
     */
    public function isType(string $type): bool
    {
        return 0 === strpos(static::TYPES[$this->id_type], $type);
    }

    /**
     * Get media.
     *
     * @todo Paginate results.
     */
    public static function getMedia(array $excluded, int $page = 1, int $limit = 10): array
    {
        return Db::getInstance()->executeS((new DbQuery())
            ->select('*')
            ->from(static::$definition['table'])
            ->where('id_media NOT IN ('.(implode(',', $excluded) ?: 0).')')
        );
    }

    /**
     * Get media URL.
     */
    public static function getMediaUrl(int $id_media, string $size = ''): string
    {
        $path = Image::getImgFolderStatic($id_media).($size ? "$id_media-$size" : $id_media).'.jpg';

        return Context::getContext()->link->getMediaLink("/img/cwmedia/$path");
    }

    /**
     * Get product media.
     */
    public static function getProductMedia(int $id_product, int $id_shop): array
    {
        $media = Db::getInstance()->executeS((new DbQuery())
            ->select('m.*')
            ->from(static::$definition['associations']['products']['association'], 'mp')
            ->naturalJoin(static::$definition['table'], 'm')
            ->where("mp.id_product = $id_product")
            ->where("mp.id_shop = $id_shop")
            ->orderBy('mp.position')
        );

        foreach ($media as &$m) {
            switch (static::TYPES[$m['id_type']]) {
                case 'video/youtube':
                    $m['src']  = static::getMediaUrl($m['id_media'], 'thumb');
                    $m['href'] = "https://www.youtube.com/embed/{$m['content']}?autoplay=1";
                    break;
                default:
                    $m['src']  = static::getMediaUrl($m['id_media'], 'thumb');
                    $m['href'] = static::getMediaUrl($m['id_media']);
                    break;
            }
        }

        return $media;
    }

    /**
     * Get product media IDs.
     */
    public static function getProductMediaIds(int $id_product): array
    {
        return array_column(Db::getInstance()->executeS((new DbQuery())
            ->select('m.id_media')
            ->from(static::$definition['associations']['products']['association'], 'mp')
            ->naturalJoin(static::$definition['table'], 'm')
            ->where("mp.id_product = $id_product")
            ->orderBy('mp.position')
        ), 'id_media');
    }

    /**
     * Set files.
     */
    protected function setFiles(): bool
    {
        if (!$this->file) {
            return true;
        }
        if (!$this->hasDirectory() and !$this->addDirectory()) {
            return false;
        }
        if (!$this->removeFiles()) {
            return false;
        }

        $sizes = $this->isType('video') ? ['thumb' => static::SIZES['thumb']] : static::SIZES;
        $sizes[''] = 0; // Source size.

        foreach ($sizes as $size => $width) {
            $destination = $this->getFile($size);
            $height = round($this->height * $width / $this->width);
            if (!$this->addFile($this->file, $destination, $width, $height)) {
                return false;
            }
        }

        $this->removeFile($this->file);
        unset($this->file); // TODO: find why Prestashop thinks it prevents a hack.

        return true;
    }

    /**
     * Add file.
     */
    protected function addFile(string $source, string $destination, int $width, int $height): bool
    {
        return ImageManager::resize($source, $destination, $width, $height);
    }

    /**
     * Remove files.
     */
    protected function removeFiles(): bool
    {
        $source = $this->getDirectory()."$this->id.jpg";
        $paths  = [$source];
        foreach (array_keys(static::SIZES) as $size) {
            $paths[] = $this->getDirectory()."$this->id-$size.jpg";
        }

        return array_product(array_map([$this, 'removeFile'], $paths));
    }

    /**
     * Remove file.
     *
     * @todo Remove `or true` when Tools::deleteFile would return a boolean.
     */
    protected function removeFile(string $path): bool
    {
        return Tools::deleteFile($path) or true;
    }

    /**
     * Get file.
     */
    protected function getFile(string $size): string
    {
        return $this->getDirectory().$this->id.($size ? "-$size" : '').'.jpg';
    }

    /**
     * Wether or not this media has a directory.
     */
    protected function hasDirectory(): bool
    {
        return Tools::file_exists_cache($this->getDirectory());
    }

    /**
     * Add file directory.
     */
    protected function addDirectory(): bool
    {
        return mkdir($this->getDirectory(), 0775, true);
    }

    /**
     * Remove file directory.
     */
    protected function removeDirectory(): bool
    {
        return rmdir($this->getDirectory());
    }

    /**
     * Get file directory.
     */
    protected function getDirectory(): string
    {
        return _PS_IMG_DIR_.'cwmedia/'.Image::getImgFolderStatic($this->id);
    }

    /**
     * Wether or not product is valid.
     *
     * @todo Validate by using each fields definition.
     */
    protected function isValidProduct(array $product): bool
    {
        return Validate::isUnsignedId($product['id_product'])
               and Validate::isUnsignedInt($product['position']);
    }

    /**
     * Get database.
     */
    protected function getDb(bool $slave = false): Db
    {
        return Db::getInstance($slave ? _PS_USE_SQL_SLAVE_ : null);
    }

    /**
     * Get database query builder.
     */
    protected function getDbQuery(): DbQuery
    {
        return new DbQuery();
    }
}
