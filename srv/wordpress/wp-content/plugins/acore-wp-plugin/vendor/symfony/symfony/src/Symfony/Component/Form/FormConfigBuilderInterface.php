<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface FormConfigBuilderInterface extends FormConfigInterface
{
    /**
     * Adds an event listener to an event on this form.
     *
     * @param string   $eventName The name of the event to listen to
     * @param callable $listener  The listener to execute
     * @param int      $priority  The priority of the listener. Listeners
     *                            with a higher priority are called before
     *                            listeners with a lower priority.
     *
     * @return $this The configuration object
     */
    public function addEventListener($eventName, $listener, $priority = 0);

    /**
     * Adds an event subscriber for events on this form.
     *
     * @return $this The configuration object
     */
    public function addEventSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Appends / prepends a transformer to the view transformer chain.
     *
     * The transform method of the transformer is used to convert data from the
     * normalized to the view format.
     * The reverseTransform method of the transformer is used to convert from the
     * view to the normalized format.
     *
     * @param bool $forcePrepend If set to true, prepend instead of appending
     *
     * @return $this The configuration object
     */
    public function addViewTransformer(DataTransformerInterface $viewTransformer, $forcePrepend = false);

    /**
     * Clears the view transformers.
     *
     * @return $this The configuration object
     */
    public function resetViewTransformers();

    /**
     * Prepends / appends a transformer to the normalization transformer chain.
     *
     * The transform method of the transformer is used to convert data from the
     * model to the normalized format.
     * The reverseTransform method of the transformer is used to convert from the
     * normalized to the model format.
     *
     * @param bool $forceAppend If set to true, append instead of prepending
     *
     * @return $this The configuration object
     */
    public function addModelTransformer(DataTransformerInterface $modelTransformer, $forceAppend = false);

    /**
     * Clears the normalization transformers.
     *
     * @return $this The configuration object
     */
    public function resetModelTransformers();

    /**
     * Sets the value for an attribute.
     *
     * @param string $name  The name of the attribute
     * @param mixed  $value The value of the attribute
     *
     * @return $this The configuration object
     */
    public function setAttribute($name, $value);

    /**
     * Sets the attributes.
     *
     * @return $this The configuration object
     */
    public function setAttributes(array $attributes);

    /**
     * Sets the data mapper used by the form.
     *
     * @return $this The configuration object
     */
    public function setDataMapper(DataMapperInterface $dataMapper = null);

    /**
     * Sets whether the form is disabled.
     *
     * @param bool $disabled Whether the form is disabled
     *
     * @return $this The configuration object
     */
    public function setDisabled($disabled);

    /**
     * Sets the data used for the client data when no value is submitted.
     *
     * @param mixed $emptyData The empty data
     *
     * @return $this The configuration object
     */
    public function setEmptyData($emptyData);

    /**
     * Sets whether errors bubble up to the parent.
     *
     * @param bool $errorBubbling
     *
     * @return $this The configuration object
     */
    public function setErrorBubbling($errorBubbling);

    /**
     * Sets whether this field is required to be filled out when submitted.
     *
     * @param bool $required
     *
     * @return $this The configuration object
     */
    public function setRequired($required);

    /**
     * Sets the property path that the form should be mapped to.
     *
     * @param string|PropertyPathInterface|null $propertyPath The property path or null if the path should be set
     *                                                        automatically based on the form's name
     *
     * @return $this The configuration object
     */
    public function setPropertyPath($propertyPath);

    /**
     * Sets whether the form should be mapped to an element of its
     * parent's data.
     *
     * @param bool $mapped Whether the form should be mapped
     *
     * @return $this The configuration object
     */
    public function setMapped($mapped);

    /**
     * Sets whether the form's data should be modified by reference.
     *
     * @param bool $byReference Whether the data should be modified by reference
     *
     * @return $this The configuration object
     */
    public function setByReference($byReference);

    /**
     * Sets whether the form should read and write the data of its parent.
     *
     * @param bool $inheritData Whether the form should inherit its parent's data
     *
     * @return $this The configuration object
     */
    public function setInheritData($inheritData);

    /**
     * Sets whether the form should be compound.
     *
     * @param bool $compound Whether the form should be compound
     *
     * @return $this The configuration object
     *
     * @see FormConfigInterface::getCompound()
     */
    public function setCompound($compound);

    /**
     * Sets the resolved type.
     *
     * @return $this The configuration object
     */
    public function setType(ResolvedFormTypeInterface $type);

    /**
     * Sets the initial data of the form.
     *
     * @param mixed $data The data of the form in model format
     *
     * @return $this The configuration object
     */
    public function setData($data);

    /**
     * Locks the form's data to the data passed in the configuration.
     *
     * A form with locked data is restricted to the data passed in
     * this configuration. The data can only be modified then by
     * submitting the form or using PRE_SET_DATA event.
     *
     * It means data passed to a factory method or mapped from the
     * parent will be ignored.
     *
     * @param bool $locked Whether to lock the default configured data
     *
     * @return $this The configuration object
     */
    public function setDataLocked($locked);

    /**
     * Sets the form factory used for creating new forms.
     */
    public function setFormFactory(FormFactoryInterface $formFactory);

    /**
     * Sets the target URL of the form.
     *
     * @param string $action The target URL of the form
     *
     * @return $this The configuration object
     */
    public function setAction($action);

    /**
     * Sets the HTTP method used by the form.
     *
     * @param string $method The HTTP method of the form
     *
     * @return $this The configuration object
     */
    public function setMethod($method);

    /**
     * Sets the request handler used by the form.
     *
     * @return $this The configuration object
     */
    public function setRequestHandler(RequestHandlerInterface $requestHandler);

    /**
     * Sets whether the form should be initialized automatically.
     *
     * Should be set to true only for root forms.
     *
     * @param bool $initialize True to initialize the form automatically,
     *                         false to suppress automatic initialization.
     *                         In the second case, you need to call
     *                         {@link FormInterface::initialize()} manually.
     *
     * @return $this The configuration object
     */
    public function setAutoInitialize($initialize);

    /**
     * Builds and returns the form configuration.
     *
     * @return FormConfigInterface
     */
    public function getFormConfig();
}
