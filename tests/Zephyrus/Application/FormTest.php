<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;
use Zephyrus\Core\Session;

class FormTest extends TestCase
{
    public function testFlattenRead()
    {
        $sessionData = array(
            "example" => "manual-feedback2",
            "CSRFToken" => "CSRFGuard_260125517...",
            "cart" => array(
                "product_id" => array("1", "2", "3", "4"),
                "quantity" => array("23", "", "", ""),
                "amount" => array("", "", "we", "")
            )
        );
        $form = new Form($sessionData);
        $this->assertEquals([
            'example' => 'manual-feedback2',
            'CSRFToken' => 'CSRFGuard_260125517...',
            'cart[product_id][0]' => '1',
            'cart[product_id][1]' => '2',
            'cart[product_id][2]' => '3',
            'cart[product_id][3]' => '4',
            'cart[quantity][0]' => '23',
            'cart[quantity][1]' => '',
            'cart[quantity][2]' => '',
            'cart[quantity][3]' => '',
            'cart[amount][0]' => '',
            'cart[amount][1]' => '',
            'cart[amount][2]' => 'we',
            'cart[amount][3]' => ''
        ], Form::getSavedFields());

        $form->field('cart', [
            Rule::associativeArray("Doit être un tableau associatif"),
            Rule::nested('product_id', [
                Rule::array("Les identifiants de produits doivent être un tableau."),
                Rule::each([
                    Rule::range(1, 10, "Le produit [%s] est inexistant.")
                ])
            ]),
            Rule::nested('quantity', [
                Rule::array("Les quantités de produits doivent être un tableau."),
                new Rule(function ($value, $fields) {
                    return count($value) == count($fields['cart']['product_id']);
                }, "La structure de quantité est invalide."),
                Rule::each([
                    Rule::required("Les quantités doivent être saisies."),
                    Rule::integer("Les quantités doivent être un entier.")
                ])
            ]),
            Rule::nested('amount', [
                Rule::array("Les montants de produits doivent être un tableau."),
                new Rule(function ($value, $fields) {
                    return count($value) == count($fields['cart']['product_id']);
                }, "La structure de montant est invalide."),
                Rule::each([
                    Rule::required("Les montants doivent être saisies."),
                    Rule::decimal("Les montants doivent être un nombre décimal.")
                ])
            ])
        ])->all()->keep();
        $form->verify();
        $form->registerFeedback();
        $this->assertEquals([
            'cart[quantity][1]' => ['Les quantités doivent être saisies.'],
            'cart[quantity][2]' => ['Les quantités doivent être saisies.'],
            'cart[quantity][3]' => ['Les quantités doivent être saisies.'],
            'cart[amount][0]' => ['Les montants doivent être saisies.'],
            'cart[amount][1]' => ['Les montants doivent être saisies.'],
            'cart[amount][2]' => ['Les montants doivent être un nombre décimal.'],
            'cart[amount][3]' => ['Les montants doivent être saisies.']
        ], Feedback::readAll());
    }

    public function testBasicSuccessfulValidations()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => 'Balesque'
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ]);
        $form->field('lastname', [
            Rule::required("Lastname must not be empty."),
            Rule::name("Lastname must be a valid name.")
        ]);

        self::assertEquals([
            'firstname' => 'Roland',
            'lastname' => 'Balesque'
        ], $form->getFields());
        self::assertEquals('Roland', $form->getValue('firstname'));
        self::assertTrue($form->isRegistered('firstname'));
        self::assertTrue($form->isRegistered('lastname'));
        self::assertFalse($form->isRegistered('email')); // Not defined in form ...
        self::assertEquals(null, $form->getValue('email')); // Not defined in form ...
        self::assertTrue($form->verify());
    }

    public function testBasicFailedValidations()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => '#0A0A0A' // Invalid name rule will trigger ...
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ]);
        $form->field('lastname', [
            Rule::required("Lastname must not be empty."),
            Rule::name("Lastname must be a valid name.")
        ]);

        self::assertFalse($form->verify());
        self::assertTrue($form->hasError());
        self::assertTrue($form->hasError('lastname'));
        self::assertEquals([
            'Lastname must be a valid name.'
        ], $form->getErrorMessages());
        self::assertEquals([
            'lastname' => ['Lastname must be a valid name.']
        ], $form->getErrors());
        self::assertEquals('Roland', Form::getSavedField('firstname'));
        self::assertEquals('', Form::getSavedField('lastname')); // Because error
        Form::removeMemorizedValue(); // Empty all form values ...
        self::assertEquals('', Form::getSavedField('firstname'));
    }

    public function testBasicFailedValidationsKeepFields()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => '#0A0A0A' // Invalid name rule will trigger ...
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ]);
        $form->field('lastname', [
            Rule::required("Lastname must not be empty."),
            Rule::name("Lastname must be a valid name.")
        ])->keep(); // Keep in session variable even on error ...

        self::assertFalse($form->verify());
        self::assertTrue($form->hasError());
        self::assertTrue($form->hasError('lastname'));
        self::assertEquals([
            'Lastname must be a valid name.'
        ], $form->getErrorMessages());
        self::assertEquals([
            'lastname' => ['Lastname must be a valid name.']
        ], $form->getErrors());
        self::assertEquals('Roland', Form::getSavedField('firstname'));
        self::assertEquals('#0A0A0A', Form::getSavedField('lastname'));
    }

    public function testOptionalFields()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => 'Balesque',
            'phone' => '' // Optional field
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ]);
        $form->field('lastname', [
            Rule::required("Lastname must not be empty."),
            Rule::name("Lastname must be a valid name.")
        ]);
        $form->field('phone', [
            Rule::phone("Phone format is invalid.")
        ])->optional();
        $form->verify();

        self::assertTrue($form->verify());
        self::assertEquals('', $form->getValue('phone'));
    }

    public function testNullableFields()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => 'Balesque',
            'bio' => '', // Nullable field with no validation
            'favorite_pet' => '' // Nullable field
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ]);
        $form->field('lastname', [
            Rule::required("Lastname must not be empty."),
            Rule::name("Lastname must be a valid name.")
        ]);
        $form->field('bio')->nullable();
        $form->field('favorite_pet', [
            Rule::inArray(['cat', 'dog', 'bird'], "Pet is invalid.")
        ])->optional()->nullable();

        self::assertTrue($form->verify());
        self::assertEquals(null, $form->getValue('bio'));
        self::assertEquals(null, $form->getValue('favorite_pet'));
        self::assertEquals((object) [
            'firstname' => 'Roland',
            'lastname' => 'Balesque',
            'bio' => null,
            'favorite_pet' => null
        ], $form->buildObject());
    }

    public function testValidateAllRules()
    {
        $form = new Form([
            'firstname' => '',
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ])->all(); // Validate everything ...

        self::assertFalse($form->verify());
        self::assertTrue($form->hasError());
        self::assertEquals([
            'Firstname must not be empty.',
            'Firstname must be a valid name.'
        ], $form->getErrorMessages());
        self::assertEquals([
            'firstname' => [
                'Firstname must not be empty.',
                'Firstname must be a valid name.'
            ]
        ], $form->getErrors());
    }

    public function testCustomRuleValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'admin'
        ]);

        // Custom rule, username must not be admin!
        $customRule = new Rule(function ($value) {
            return $value != 'admin';
        }, 'Username is not valid.');
        $form->field('username', [$customRule]);
        self::assertFalse($form->verify());
        self::assertEquals('Username is not valid.', $form->getErrorMessages()[0]);
    }

    public function testCustomRuleValidationWithFields()
    {
        $form = new Form();
        $form->addFields([
            'password' => 'omega1',
            'password_confirm' => 'omega'
        ]);

        // Custom rule using all form fields
        $form->field('password', [
            Rule::required("Password must not be empty."),
            Rule::passwordCompliant("Password is not compliant.")
        ]);
        $form->field('password_confirm', [
            Rule::required("Password confirmation must not be empty."),
            Rule::sameAs('password', "Password confirmation is not the same.")
        ]);
        self::assertFalse($form->verify());
        self::assertEquals([
            'password' => [
                'Password is not compliant.'
            ],
            'password_confirm' => [
                'Password confirmation is not the same.'
            ]
        ], $form->getErrors());
    }

    public function testUnregisteredValidations()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => 'Balesque' // Must have a checkbox "agree", but was not submitted ...
        ]);
        $form->field('firstname', [
            Rule::required("Firstname must not be empty."),
            Rule::name("Firstname must be a valid name.")
        ]);
        $form->field('lastname', [
            Rule::required("Lastname must not be empty."),
            Rule::name("Lastname must be a valid name.")
        ]);
        $form->field('agree', [
            Rule::required("You must agree to the terms of service.")
        ]);

        self::assertFalse($form->verify());
        self::assertEquals([
            'You must agree to the terms of service.'
        ], $form->getErrorMessages());
        self::assertEquals([
            'agree' => ['You must agree to the terms of service.']
        ], $form->getErrors());
    }

    public function testRemoveFields()
    {
        $form = new Form([
            'firstname' => 'Roland',
            'lastname' => 'Balesque',
            'phone' => '555-555-5555'
        ]);
        $form->addField('email', 'test@test.com');

        self::assertTrue($form->isRegistered('firstname'));
        self::assertTrue($form->isRegistered('lastname'));
        self::assertTrue($form->isRegistered('phone'));
        self::assertTrue($form->isRegistered('email'));
        self::assertEquals([
            'firstname' => 'Roland',
            'lastname' => 'Balesque',
            'phone' => '555-555-5555',
            'email' => 'test@test.com'
        ], $form->getFields());
        $form->removeField('phone');
        $form->removeField('email');
        self::assertFalse($form->isRegistered('phone'));
        self::assertFalse($form->isRegistered('email'));
        self::assertEquals([
            'firstname' => 'Roland',
            'lastname' => 'Balesque'
        ], $form->getFields());
    }

    public function testBuildObject()
    {
        $form = new Form();
        $form->addFields(['name' => 'bob', 'price' => '10.00']);
        $object = $form->buildObject();
        self::assertEquals('bob', $object->name);
        self::assertEquals('10.00', $object->price);
    }

    public function testReadDefaultValue()
    {
        $form = new Form([
            'username' => 'blewis',
            'firstname' => 'bob'
        ]);
        self::assertEquals('my_default', $form->getValue('test', 'my_default'));
    }

    public function testManualErrorMessages()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        $form->field('username', [
            Rule::notEmpty('username not empty')
        ]);
        $form->addError('name', 'name must not be empty');
        self::assertFalse($form->verify()); // The defined validation pass, but there has been a manual error entered
        self::assertTrue(key_exists('name', $form->getErrors()));
        self::assertEquals('name must not be empty', $form->getErrors()['name'][0]);
    }

    public function testFeedback()
    {
        $session = new Session();
        $session->start();
        $form = new Form([
            'username' => ''
        ]);
        $form->field('username', [
            Rule::notEmpty('Username must not be empty.')
        ]);
        self::assertFalse($form->verify());
        $form->registerFeedback();
        $feedback = Feedback::readAll();
        self::assertTrue(key_exists('username', $feedback));
        self::assertEquals('Username must not be empty.', $feedback['username'][0]);
        Session::destroy();
    }

    public function testFlash()
    {
        $session = new Session();
        $session->start();
        $form = new Form([
            'username' => ''
        ]);
        $form->field('username', [
            Rule::notEmpty('Username must not be empty.')
        ]);
        self::assertFalse($form->verify());
        $form->registerFlash();
        $flash = Flash::readAll()->error;
        self::assertEquals('Username must not be empty.', $flash[0]);
        Session::destroy();
    }
}
