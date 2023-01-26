<?php

declare(strict_types=1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace AltF4\SyliusPaycomBacPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of SyliusPaycomBacConfigurationType
 *
 * @author smolina
 */
class SyliusPaycomBacConfigurationType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('key_id', TextType::class, [
                    'label' => 'altf4.paycom_bac.form.key_id'
                ])
                ->add('username', TextType::class, [
                    'label' => 'altf4.paycom_bac.form.username'
                ])                
                ->add('secret_key', TextType::class, [
                    'label' => 'altf4.paycom_bac.form.secret_key'
                ])
                ->add('merchant_id', TextType::class, [
                    'label' => 'altf4.paycom_bac.form.merchant_id'
                ])
                ->add('use_authorize', HiddenType::class, ['data'=> true])
                ;
    }
}
