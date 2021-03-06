<?hh
namespace Titon\Intl;

use Titon\Cache\Storage\MemoryStorage;
use Titon\Io\Reader\HackReader;
use Titon\Io\Reader\IniReader;
use Titon\Io\Reader\JsonReader;
use Titon\Io\Reader\PhpReader;
use Titon\Test\TestCase;

/**
 * @property \Titon\Intl\MessageLoader $object
 */
class MessageLoaderTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();

        $this->object = new MessageLoader(Vector {new HackReader()});

        $this->translator = new Translator($this->object);
        $this->translator->addResourcePaths('test', Set {TEMP_DIR . '/intl/'});
        $this->translator->addLocale(new Locale('ex_CH'));
    }

    public function testGetAddReader(): void {
        $reader1 = new HackReader();
        $this->assertEquals(Vector {$reader1}, $this->object->getReaders());

        $reader2 = new PhpReader();
        $this->object->addReader($reader2);

        $this->assertEquals(Vector {$reader1, $reader2}, $this->object->getReaders());

        $reader3 = new IniReader();
        $reader4 = new JsonReader();
        $this->object->addReaders(Vector {$reader3, $reader4});

        $this->assertEquals(Vector {$reader1, $reader2, $reader3, $reader4}, $this->object->getReaders());
    }

    public function testGetSetStorage(): void {
        $this->assertEquals(null, $this->object->getStorage());

        $storage = new MemoryStorage();
        $this->object->setStorage($storage);

        $this->assertEquals($storage, $this->object->getStorage());
    }

    public function testGetSetTranslator(): void {
        $this->assertSame($this->translator, $this->object->getTranslator());

        $translator = new Translator($this->object);
        $this->object->setTranslator($translator);

        $this->assertSame($translator, $this->object->getTranslator());
    }

    public function testLoadCatalog(): void {
        $this->translator->localize('ex_CH');

        $this->assertEquals(new Catalog('foo', 'test', Map {
            'a' => 'Aenean tellus lectus',
            'b' => 'Dolor sit amet',
            'c' => 'Consectetur adipiscing elit'
        }), $this->object->loadCatalog('test', 'foo'));

        $this->translator->localize('ex');

        $this->assertEquals(new Catalog('foo', 'test', Map {
            'a' => 'Lorem ipsum',
            'b' => 'Dolor sit amet'
        }), $this->object->loadCatalog('test', 'foo'));
    }

    public function testLoadCatalogMissingCatalog(): void {
        $this->translator->localize('ex_CH');

        $this->assertEquals(new Catalog('baz', 'test', Map {}), $this->object->loadCatalog('test', 'baz'));
    }

    public function testLoadCatalogCachesMessages(): void {
        $cacheKey = 'intl.catalog.test.foo.ex';
        $storage = new MemoryStorage();
        $this->object->setStorage($storage);

        $this->assertFalse($storage->has($cacheKey));
        $this->assertEquals(null, $storage->getItem($cacheKey)->get());

        $this->translator->localize('ex');
        $this->object->loadCatalog('test', 'foo');

        $this->assertTrue($storage->has($cacheKey));
        $this->assertEquals(Map {
            'a' => 'Lorem ipsum',
            'b' => 'Dolor sit amet'
        }, $storage->getItem($cacheKey)->get());
    }

    public function testTranslate(): void {
        $this->translator->addResourcePaths('test', Set {SRC_DIR . '/Titon/Intl/'});
        $this->translator->addLocale(new Locale('en_US'));
        $this->translator->localize('en_US');

        $this->assertEquals('{0} health, {1} energy, {2} damage', $this->translator->translate('test.bar.format'));
        $this->assertEquals('1,337 health, 666 energy, 255 damage', $this->translator->translate('test.bar.format', Vector {1337, 666, 255}));
    }

    /**
     * @expectedException \Titon\Intl\Exception\MissingMessageException
     */
    public function testTranslateMissingMessageThrowsError(): void {
        $this->translator->localize('ex_CH');

        $this->assertEquals('{0} health, {1} energy, {2} damage', $this->translator->translate('test.bar.missing'));
    }

}
