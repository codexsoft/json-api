<?php

namespace CodexSoft\JsonApi\Response;

use CodexSoft\JsonApi\Form\DefaultFormFactoryGenerator;
use Symfony\Component\Form\FormBuilderInterface;

trait ResponseWrappedDataTrait
{
    public static $generatingWrappedDataForResponseDefinition = false;

    /**
     * @param bool $generatingWrappedDataForResponseDefinition
     */
    public static function setGeneratingWrappedDataForResponseDefinition(bool $generatingWrappedDataForResponseDefinition): void
    {
        self::$generatingWrappedDataForResponseDefinition = $generatingWrappedDataForResponseDefinition;
    }

    /**
     * @return bool
     */
    public static function isGeneratingWrappedDataForResponseDefinition(): bool
    {
        return self::$generatingWrappedDataForResponseDefinition;
    }

    public function wrapData($data): array
    {
        // auto-wrap data if on
        if ($this->getDataWrapper()) {
            $wrappedData = [];
            $wrappedData[$this->getDataWrapper()] = $data;
            $data = $wrappedData;
        }
        return $data;
    }

    public function wrapDefinition(FormBuilderInterface $builder)
    {
        if ($this->getDataWrapper()) {
            $reflection = new \ReflectionClass($this);

            if ($reflection->isAnonymous()) {
                return;
            }

            $children = $builder->all();

            /** @var static $xClass */
            eval('$xClass = new class extends '.\get_class($this).' {};');
            $anClassName = \get_class($xClass);
            $wrappedFormBuilder = self::generateFormFactory()->createNamedBuilder($this->getDataWrapper(), $anClassName, null, $this->getDataWrapperOptions());

            foreach ($children as $name => $child) {
                $wrappedFormBuilder->add($child);
            }

            foreach ($children as $name => $child) {
                $builder->remove($name);
            }

            $builder->add($wrappedFormBuilder);
        }
    }

    /**
     * Override and return something like 'data' in order to auto-wrap response data into key 'data'
     * @return null|string
     */
    public function getDataWrapper(): ?string
    {
        return null;
    }

    public function getDataWrapperOptions(): array
    {
        return [
            'label' => 'Возвращенные данные',
        ];
    }

    public static function generateFormFactory()
    {
        return DefaultFormFactoryGenerator::generate();
    }

}