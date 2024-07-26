<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

interface SymfonySerializer extends ContextAwareDecoderInterface, ContextAwareEncoderInterface, DenormalizerInterface, NormalizerInterface, SerializerInterface {}
