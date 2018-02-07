<?php

use PHPUnit\Framework\TestCase;

class CWMediaTest extends TestCase
{
    const REQUIRED_HOOKS = [
        'actionAdminControllerSetMedia',
        'actionAdminProductsControllerDuplicateAfter',
        'actionProductAdd',
        'actionProductDelete',
        'actionProductUpdate',
        'displayAdminProductsExtra',
        'displayHeader',
        'displayProductMedia',
    ];
    const REQUIRED_MODELS = ['ProductMedia'];
    const REQUIRED_PROPERTIES = [
        'author',
        'confirmUninstall',
        'description',
        'displayName',
        'name',
        'ps_versions_compliancy',
        'tab',
        'version',
    ];
    const REQUIRED_TABS = [
        [
            'name'       => 'Media',
            'class_name' => 'AdminCWMedia',
            'module'     => 'cwmedia',
            'id_parent'  => 9, // Catalog menu
        ],
    ];

    /**
     * New instance should have required properties.
     */
    public function testInstanceHasRequiredProperties()
    {
        $module = new CWMedia();
        foreach (self::REQUIRED_PROPERTIES as $prop) {
            $this->assertNotNull($module->$prop);
        }
    }

    /**
     * CWMedia::install() should add (image) directory, required hooks, models
     * and tabs.
     */
    public function testInstall()
    {
        $mock = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'addDirectory',
                'addHooks',
                'addModels',
                'addTabs',
            ])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('addDirectory')
            ->with($this->equalTo(_PS_IMG_DIR_."$mock->name/"))
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method('addHooks')
            ->with($this->equalTo(self::REQUIRED_HOOKS))
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method('addModels')
            ->with($this->equalTo(self::REQUIRED_MODELS))
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method('addTabs')
            ->with($this->equalTo(self::REQUIRED_TABS))
            ->willReturn(true);

        $mock->install();
    }

    /**
     * CWBundle::uninstall() should clear cache and remove (image) directory and
     * required models and tabs.
     */
    public function testUninstall()
    {
        $mock = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                '_clearCache',
                'removeDirectory',
                'removeModels',
                'removeTabs',
            ])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('_clearCache')
            ->with($this->equalTo('*'));
        $mock
            ->expects($this->once())
            ->method('removeDirectory')
            ->with($this->equalTo(_PS_IMG_DIR_."$mock->name/"))
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method('removeModels')
            ->with($this->equalTo(self::REQUIRED_MODELS))
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method('removeTabs')
            ->with($this->equalTo(self::REQUIRED_TABS))
            ->willReturn(true);

        $mock->uninstall();
    }

    /**
     * CWMedia::hookDisplayAdminProductsExtra() should not set template
     * variables if template is already cached.
     */
    public function testDisplayAdminProductsWithCache()
    {
        $mock = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();

        $mock->method('isCached')->willReturn(true);

        $mock->expects($this->never())->method('setTemplateVars');

        $mock->hookDisplayAdminProductsExtra([]);
    }

    /**
     * CWMedia::hookDisplayAdminProductsExtra() should set required template
     * variables.
     */
    public function testDisplayAdminProducts()
    {
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'getContextShopId',
                'getControllerLink',
                'getJson',
                'getMediaUploader',
                'getMediaUploaderHints',
                'getProductMedia',
                'getValue',
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();
        $mock_smarty = $this
            ->getMockBuilder('stdClass')
            ->setMockClassName('Smarty_Internal_Data')
            ->getMock();
        $mock_uploader = $this
            ->getMockBuilder('HelperMediaUploader')
            ->setMethods(['render'])
            ->getMock();

        $mock_module->method('isCached')->willReturn(false);
        $mock_module->method('getContextShopId')->willReturn(2);
        $mock_module->method('getControllerLink')->willReturn('controller link');
        $mock_module->method('getJson')->willReturn('{"json"}');
        $mock_module->method('getMediaUploader')->willReturn($mock_uploader);
        $mock_module->method('getMediaUploaderHints')->willReturn('hints');
        $mock_module->method('getProductMedia')->willReturn(['media']);
        $mock_module->method('getValue')->willReturn(1);
        $mock_uploader->method('render')->willReturn('uploader template string');

        $mock_module
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with($this->equalTo([
                'hints'    => 'hints',
                'json'     => '{"json"}',
                'media'    => ['media'],
                'tab_name' => 'ModuleCwmedia',
                'uploader' => 'uploader template string',
            ]))
            ->willReturn($mock_smarty);

        $mock_module->hookDisplayAdminProductsExtra([]);
    }

    /**
     * Provide data to CWMediaTest::testActionProductAddDoNothing().
     */
    public function provideTestActionProductAddDoNothing()
    {
        return [
            'ajax_request'      => ['POST' => ['ajax' => true]],
            'tab_not_submitted' => ['POST' => []],
        ];
    }

    /**
     * CWMedia::hookActionProductAdd() should not add new media nor set product
     * media if request is AJAX or admin product tab is not submitted.
     *
     * @dataProvider provideTestActionProductAddDoNothing
     */
    public function testActionProductAddDoNothing(array $POST)
    {
        $_POST = $POST;
        $mock = $this
            ->getMockBuilder('CWMedia')
            ->setMethods(['addMediaAndSetProductMedia'])
            ->getMock();

        $mock->expects($this->never())->method('addMediaAndSetProductMedia');

        $mock->hookActionProductAdd(['id_product' => 1]);
    }

    /**
     * Provide data to CWMediaTest::testActionProductAddNoAdd().
     */
    public function provideTestActionProductAddNoAdd()
    {
        return [
            'empty_upload' => [
                'POST'  => ['ajax' => false, 'submitted_tabs' => ['ModuleCwmedia']],
                'FILES' => [['size' => 0]],
            ],
            'no_new_media' => [
                'POST'  => ['ajax' => false, 'submitted_tabs' => ['ModuleCwmedia'], 'media' => [['id_media' => 1]]],
                'FILES' => [],
            ],
        ];
    }

    /**
     * CWMedia::hookActionProductAdd() should not add new media if nothing has
     * been uploaded or all media already exists.
     *
     * @dataProvider provideTestActionProductAddNoAdd
     */
    public function testActionProductAddNoAdd(array $POST, array $FILES)
    {
        $_POST = $POST;
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'addMedia',
                'getContextShopsIds',
                'getMediaUploader',
                'setProductMedia',
            ])
            ->getMock();
        $mock_uploader = $this
            ->getMockBuilder('HelperMediaUploader')
            ->setMethods(['process'])
            ->getMock();

        $mock_module->method('getMediaUploader')->willReturn($mock_uploader);
        $mock_uploader->method('process')->willReturn($FILES);

        $mock_module->expects($this->never())->method('addMedia');

        $mock_module->hookActionProductAdd(['id_product' => 1]);
    }

    /**
     * Provide data to CWMediaTest::provideTestActionProductAddNewMedia().
     */
    public function provideTestActionProductAddNewMedia()
    {
        return [
            'new_link'   => [
                'POST'  => ['ajax' => false, 'submitted_tabs' => ['ModuleCwmedia'], 'media' => [['id_media' => 0]]],
                'FILES' => [],
            ],
            'new_upload' => [
                'POST'  => ['ajax' => false, 'submitted_tabs' => ['ModuleCwmedia']],
                'FILES' => [['size' => 1]],
            ],
        ];
    }

    /**
     * CWMedia::hookActionProductAdd() should add new media and set them as
     * product media.
     *
     * @dataProvider provideTestActionProductAddNewMedia
     */
    public function testActionProductAddNewMedia(array $POST, array $FILES)
    {
        $_POST = $POST;
        $mock_media = $this
            ->getMockBuilder('ProductMedia')
            ->setMethods(['addProduct'])
            ->getMock();
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'addMedia',
                'getContextShopsIds',
                'getMedia',
                'getMediaUploader',
            ])
            ->getMock();
        $mock_uploader = $this
            ->getMockBuilder('HelperMediaUploader')
            ->setMethods(['process'])
            ->getMock();

        $mock_module->method('getContextShopsIds')->willReturn([1]);
        $mock_module->method('getMedia')->willReturn($mock_media);
        $mock_module->method('getMediaUploader')->willReturn($mock_uploader);
        $mock_uploader->method('process')->willReturn($FILES);

        $mock_module
            ->expects($this->once())
            ->method('addMedia')
            ->with($this->equalTo($POST['media'][0] ?? $FILES[0]))
            ->will($this->returnCallback(function (&$media) {
                $media['id_media'] = 1;

                return true;
            }));
        $mock_media
            ->expects($this->once())
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 1, 'position' => 1]))
            ->willReturn(true);

        $mock_module->hookActionProductAdd(['id_product' => 1]);
    }

    /**
     * Provide data to CWMediaTest::provideTestActionProductAddSetProductMedia().
     */
    public function provideTestActionProductAddSetProductMedia()
    {
        return [
            'new_and_existing_media' => [
                'POST'  => [
                    'ajax'           => false,
                    'submitted_tabs' => ['ModuleCwmedia'],
                    'media'          => [['id_media' => 0], ['id_media' => 1]],
                ],
                'FILES' => [['size' => 1]],
            ],
        ];
    }

    /**
     * CWMedia::hookActionProductAdd() should set product media.
     *
     * @dataProvider provideTestActionProductAddSetProductMedia
     */
    public function testActionProductAddSetProductMedia(array $POST, array $FILES)
    {
        $_POST = $POST;
        $mock_media = $this
            ->getMockBuilder('ProductMedia')
            ->setMethods(['addProduct'])
            ->getMock();
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'addMedia',
                'getContextShopsIds',
                'getMedia',
                'getMediaUploader',
            ])
            ->getMock();
        $mock_uploader = $this
            ->getMockBuilder('HelperMediaUploader')
            ->setMethods(['process'])
            ->getMock();

        $id_product = 1;
        $mock_module->method('addMedia')->will($this->returnCallback(function (&$media) use (&$id_product) {
            $media['id_media'] = $id_product++;

            return true;
        }));
        $mock_module->method('getContextShopsIds')->willReturn([1]);
        $mock_module->method('getMedia')->willReturn($mock_media);
        $mock_module->method('getMediaUploader')->willReturn($mock_uploader);
        $mock_uploader->method('process')->willReturn($FILES);

        $mock_media
            ->expects($this->at(0))
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 1, 'position' => 1]))
            ->willReturn(true);
        $mock_media
            ->expects($this->at(1))
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 1, 'position' => 2]))
            ->willReturn(true);
        $mock_media
            ->expects($this->at(2))
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 1, 'position' => 3]))
            ->willReturn(true);

        $mock_module->hookActionProductAdd(['id_product' => 1]);
    }

    /**
     * CWMedia::hookActionProductDelete() should delete product media.
     */
    public function testActionProductDelete()
    {
        $mock_media = $this
            ->getMockBuilder('ProductMedia')
            ->setMethods(['removeProduct'])
            ->getMock();
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'getContextShopsIds',
                'getMedia',
                'getProductMediaIds',
            ])
            ->getMock();

        $mock_module->method('getContextShopsIds')->willReturn([1, 2]);
        $mock_module->method('getMedia')->willReturn($mock_media);
        $mock_module->method('getProductMediaIds')->willReturn([1, 2, 3]);

        $mock_media
            ->expects($this->at(0))
            ->method('removeProduct')
            ->with($this->equalTo(1), $this->equalTo([1, 2]))
            ->willReturn(true);
        $mock_media
            ->expects($this->at(1))
            ->method('removeProduct')
            ->with($this->equalTo(1), $this->equalTo([1, 2]))
            ->willReturn(true);
        $mock_media
            ->expects($this->at(2))
            ->method('removeProduct')
            ->with($this->equalTo(1), $this->equalTo([1, 2]))
            ->willReturn(true);

        $mock_module->hookActionProductDelete(['id_product' => 1]);
    }

    /**
     * CWMedia::hookActionAdminProductsControllerDuplicateAfter() should
     * duplicate product media.
     */
    public function testActionProductDuplicate()
    {
        $mock_media = $this
            ->getMockBuilder('ProductMedia')
            ->setMethods(['addProduct'])
            ->getMock();
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'getContextShopsIds',
                'getMedia',
                'getProductMediaIds',
                'getValue',
            ])
            ->getMock();

        $mock_module->new_id_product = 2;
        $mock_module->method('getContextShopsIds')->willReturn([1, 2]);
        $mock_module->method('getMedia')->willReturn($mock_media);
        $mock_module->method('getProductMediaIds')->willReturn([1, 2, 3]);
        $mock_module->method('getValue')->willReturn(1);

        $mock_media
            ->expects($this->at(0))
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 2, 'position' => 1]), $this->equalTo([1, 2]))
            ->willReturn(true);
        $mock_media
            ->expects($this->at(1))
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 2, 'position' => 2]), $this->equalTo([1, 2]))
            ->willReturn(true);
        $mock_media
            ->expects($this->at(2))
            ->method('addProduct')
            ->with($this->equalTo(['id_product' => 2, 'position' => 3]), $this->equalTo([1, 2]))
            ->willReturn(true);

        $mock_module->hookActionAdminProductsControllerDuplicateAfter(['id_product' => 1]);
    }

    /**
     * CWMedia::hookDisplayProductMedia() should not set template variables if
     * template is already cached.
     */
    public function testDisplayProductMediaWithCache()
    {
        $mock = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();

        $mock->method('isCached')->willReturn(true);

        $mock->expects($this->never())->method('setTemplateVars');

        $mock->hookDisplayProductMedia([]);
    }

    /**
     * CWMedia::hookDisplayProductMedia() should set required template
     * variables.
     */
    public function testDisplayProductMedia()
    {
        $mock_module = $this
            ->getMockBuilder('CWMedia')
            ->setMethods([
                'getContextShopId',
                'getProductMedia',
                'getValue',
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();
        $mock_smarty = $this
            ->getMockBuilder('stdClass')
            ->setMockClassName('Smarty_Internal_Data')
            ->getMock();

        $mock_module->method('isCached')->willReturn(false);
        $mock_module->method('getValue')->willReturn(1);
        $mock_module->method('getContextShopId')->willReturn(2);
        $mock_module->method('getProductMedia')->willReturn($media = ['media']);

        $mock_module
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with($this->equalTo(['media' => $media]))
            ->willReturn($mock_smarty);

        $mock_module->hookDisplayProductMedia([]);
    }
}
