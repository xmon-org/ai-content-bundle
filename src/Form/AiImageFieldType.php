<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Image field with AI regeneration capabilities.
 *
 * This form type provides an image field with a "Regenerate with AI" button
 * that opens a modal for generating images with different styles.
 *
 * Note: This type is mainly for display/UI purposes. The actual image
 * field should be configured separately (e.g., SonataMediaType).
 *
 * Usage in Sonata Admin:
 *
 *     protected function configureFormFields(FormMapper $form): void
 *     {
 *         $form
 *             // Regular image field (SonataMedia, VichUploader, etc.)
 *             ->add('imagenDestacada', \Sonata\MediaBundle\Form\Type\MediaType::class, [
 *                 'context' => 'noticias',
 *                 'provider' => 'sonata.media.provider.image',
 *             ])
 *             // AI regeneration widget
 *             ->add('aiImageRegeneration', AiImageFieldType::class, [
 *                 'label' => false,
 *                 'mapped' => false,
 *                 'ai_regenerate_endpoint' => $this->generateUrl('admin_entity_regenerate_image', ['id' => $entity->getId()]),
 *                 'ai_generate_subject_endpoint' => $this->generateUrl('admin_entity_generate_subject', ['id' => $entity->getId()]),
 *                 'ai_current_image_url' => $currentImageUrl,
 *                 'ai_subject' => $entity->getImageSubject(),
 *             ]);
 *     }
 */
class AiImageFieldType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Endpoint to regenerate the image
            'ai_regenerate_endpoint' => null,

            // Endpoint to generate a new subject with AI
            'ai_generate_subject_endpoint' => null,

            // Endpoint to use an image from history
            'ai_use_history_endpoint' => null,

            // Endpoint to delete an image from history
            'ai_delete_history_endpoint' => null,

            // Current image URL (for preview)
            'ai_current_image_url' => null,

            // Current image ID
            'ai_current_image_id' => null,

            // Current subject
            'ai_subject' => '',

            // Current style
            'ai_style' => '',

            // Image history data
            'ai_history' => [],

            // Button labels
            'ai_regenerate_button_label' => 'Regenerate with AI',
            'ai_generate_subject_button_label' => 'Generate description',

            // Modal title
            'ai_modal_title' => 'Regenerate Image with AI',

            // Show style selector in modal
            'ai_show_style_selector' => true,

            // Show history in modal
            'ai_show_history' => true,

            // Button CSS classes
            'ai_button_class' => 'btn btn-primary',

            // Template
            'ai_template' => '@XmonAiContent/admin/form/ai_image_field.html.twig',

            // Modal template
            'ai_modal_template' => '@XmonAiContent/admin/form/ai_image_modal.html.twig',

            // Not mapped to entity
            'mapped' => false,

            // No data transformation
            'compound' => false,
        ]);

        $resolver->setAllowedTypes('ai_regenerate_endpoint', ['null', 'string']);
        $resolver->setAllowedTypes('ai_generate_subject_endpoint', ['null', 'string']);
        $resolver->setAllowedTypes('ai_use_history_endpoint', ['null', 'string']);
        $resolver->setAllowedTypes('ai_delete_history_endpoint', ['null', 'string']);
        $resolver->setAllowedTypes('ai_current_image_url', ['null', 'string']);
        $resolver->setAllowedTypes('ai_current_image_id', ['null', 'int', 'string']);
        $resolver->setAllowedTypes('ai_subject', ['null', 'string']);
        $resolver->setAllowedTypes('ai_style', ['null', 'string']);
        $resolver->setAllowedTypes('ai_history', 'array');
        $resolver->setAllowedTypes('ai_regenerate_button_label', 'string');
        $resolver->setAllowedTypes('ai_generate_subject_button_label', 'string');
        $resolver->setAllowedTypes('ai_modal_title', 'string');
        $resolver->setAllowedTypes('ai_show_style_selector', 'bool');
        $resolver->setAllowedTypes('ai_show_history', 'bool');
        $resolver->setAllowedTypes('ai_button_class', 'string');
        $resolver->setAllowedTypes('ai_template', 'string');
        $resolver->setAllowedTypes('ai_modal_template', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['ai_regenerate_endpoint'] = $options['ai_regenerate_endpoint'];
        $view->vars['ai_generate_subject_endpoint'] = $options['ai_generate_subject_endpoint'];
        $view->vars['ai_use_history_endpoint'] = $options['ai_use_history_endpoint'];
        $view->vars['ai_delete_history_endpoint'] = $options['ai_delete_history_endpoint'];
        $view->vars['ai_current_image_url'] = $options['ai_current_image_url'];
        $view->vars['ai_current_image_id'] = $options['ai_current_image_id'];
        $view->vars['ai_subject'] = $options['ai_subject'];
        $view->vars['ai_style'] = $options['ai_style'];
        $view->vars['ai_history'] = $options['ai_history'];
        $view->vars['ai_regenerate_button_label'] = $options['ai_regenerate_button_label'];
        $view->vars['ai_generate_subject_button_label'] = $options['ai_generate_subject_button_label'];
        $view->vars['ai_modal_title'] = $options['ai_modal_title'];
        $view->vars['ai_show_style_selector'] = $options['ai_show_style_selector'];
        $view->vars['ai_show_history'] = $options['ai_show_history'];
        $view->vars['ai_button_class'] = $options['ai_button_class'];
        $view->vars['ai_template'] = $options['ai_template'];
        $view->vars['ai_modal_template'] = $options['ai_modal_template'];
    }

    public function getBlockPrefix(): string
    {
        return 'xmon_ai_image';
    }
}
