<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Textarea field with AI text generation button.
 *
 * This form type extends TextareaType and adds a "Generate with AI" button
 * that triggers an AJAX call to generate content.
 *
 * Usage in Sonata Admin:
 *
 *     protected function configureFormFields(FormMapper $form): void
 *     {
 *         $form->add('imageSubject', AiTextFieldType::class, [
 *             'label' => 'Image Subject',
 *             'ai_endpoint' => $this->generateUrl('admin_entity_generate_subject', ['id' => $entity->getId()]),
 *             'ai_button_label' => 'Generate Subject',
 *             'ai_source_fields' => ['title', 'summary'], // Fields to use as context
 *         ]);
 *     }
 */
class AiTextFieldType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // AI generation endpoint URL
            'ai_endpoint' => null,

            // Button label
            'ai_button_label' => 'Generate with AI',

            // HTTP method for the endpoint
            'ai_method' => 'POST',

            // Source field names to include as context in the request
            'ai_source_fields' => [],

            // Extra data to send in the request
            'ai_extra_data' => [],

            // Button CSS class
            'ai_button_class' => 'btn btn-sm btn-outline-primary',

            // Show loading spinner
            'ai_show_spinner' => true,

            // Confirm before generating (overwrite existing content)
            'ai_confirm_overwrite' => true,

            // Confirmation message
            'ai_confirm_message' => 'This will replace the current content. Continue?',

            // Template for rendering
            'ai_template' => '@XmonAiContent/admin/form/ai_text_field.html.twig',
        ]);

        $resolver->setAllowedTypes('ai_endpoint', ['null', 'string']);
        $resolver->setAllowedTypes('ai_button_label', 'string');
        $resolver->setAllowedTypes('ai_method', 'string');
        $resolver->setAllowedTypes('ai_source_fields', 'array');
        $resolver->setAllowedTypes('ai_extra_data', 'array');
        $resolver->setAllowedTypes('ai_button_class', 'string');
        $resolver->setAllowedTypes('ai_show_spinner', 'bool');
        $resolver->setAllowedTypes('ai_confirm_overwrite', 'bool');
        $resolver->setAllowedTypes('ai_confirm_message', 'string');
        $resolver->setAllowedTypes('ai_template', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['ai_endpoint'] = $options['ai_endpoint'];
        $view->vars['ai_button_label'] = $options['ai_button_label'];
        $view->vars['ai_method'] = $options['ai_method'];
        $view->vars['ai_source_fields'] = $options['ai_source_fields'];
        $view->vars['ai_extra_data'] = $options['ai_extra_data'];
        $view->vars['ai_button_class'] = $options['ai_button_class'];
        $view->vars['ai_show_spinner'] = $options['ai_show_spinner'];
        $view->vars['ai_confirm_overwrite'] = $options['ai_confirm_overwrite'];
        $view->vars['ai_confirm_message'] = $options['ai_confirm_message'];
        $view->vars['ai_template'] = $options['ai_template'];
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'xmon_ai_text';
    }
}
