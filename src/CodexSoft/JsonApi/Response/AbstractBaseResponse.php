<?php

namespace CodexSoft\JsonApi\Response;

use CodexSoft\JsonApi\Documentation\Collector\Interfaces\SwagenResponseInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;

abstract class AbstractBaseResponse extends JsonResponse implements FormTypeInterface, SwagenResponseInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    final public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    final public function finishView(FormView $view, FormInterface $form, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    final public function getBlockPrefix()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    final public function getParent()
    {
        return FormType::class;
    }

}