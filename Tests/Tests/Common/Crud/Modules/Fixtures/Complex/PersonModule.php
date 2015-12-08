<?php

namespace Iddigital\Cms\Core\Tests\Common\Crud\Modules\Fixtures\Complex;

use Iddigital\Cms\Core\Common\Crud\CrudModule;
use Iddigital\Cms\Core\Common\Crud\Definition\CrudModuleDefinition;
use Iddigital\Cms\Core\Common\Crud\Definition\Form\CrudFormDefinition;
use Iddigital\Cms\Core\Common\Crud\Definition\Table\SummaryTableDefinition;
use Iddigital\Cms\Core\Common\Crud\Form\ObjectForm;
use Iddigital\Cms\Core\Form\Builder\Form;
use Iddigital\Cms\Core\Form\Field\Builder\Field;
use Iddigital\Cms\Core\Model\Object\ArrayDataObject;
use Iddigital\Cms\Core\Table\Builder\Column;
use Iddigital\Cms\Core\Tests\Common\Crud\Modules\Fixtures\Complex\Domain\Adult;
use Iddigital\Cms\Core\Tests\Common\Crud\Modules\Fixtures\Complex\Domain\Child;
use Iddigital\Cms\Core\Tests\Common\Crud\Modules\Fixtures\Complex\Domain\Colour;
use Iddigital\Cms\Core\Tests\Common\Crud\Modules\Fixtures\Complex\Domain\Person;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PersonModule extends CrudModule
{
    /**
     * Defines the structure of this module.
     *
     * @param CrudModuleDefinition $module
     */
    protected function defineCrudModule(CrudModuleDefinition $module)
    {
        $module->name('people-module');

        $module->labelObjects()->fromCallback(function (Person $person) {
            return $person->getFullName();
        });

        $module->objectAction('swap-names')
            ->authorize(self::EDIT_PERMISSION)
            ->where(function (Person $person) {
                return $person instanceof Adult;
            })
            ->form(Form::create()->section('Details', [
                Field::name('swap_with')->label('Swap With')->entityFrom($this->dataSource)->required()
            ]))
            ->handler(function (Person $person, ArrayDataObject $input) {
                /** @var Person $otherPerson */
                $otherPerson = $input['swap_with'];

                $tempFirstName = $otherPerson->firstName;
                $tempLastName = $otherPerson->lastName;
                $otherPerson->firstName = $person->firstName;
                $otherPerson->lastName = $person->lastName;
                $person->firstName = $tempFirstName;
                $person->lastName = $tempLastName;

                $this->dataSource->saveAll([$person, $otherPerson]);
            });

        $module->crudForm(function (CrudFormDefinition $form) {
            $form->section('Person Details', [
                    $form->field(Field::name('first_name')->label('First Name')->string()->required())
                            ->bindToProperty(Person::FIRST_NAME),
                    $form->field(Field::name('last_name')->label('Last Name')->string()->required())
                            ->bindToProperty(Person::LAST_NAME),
            ]);

            $form->dependentOnObject(function (CrudFormDefinition $form, Person $object = null) {
                $form->section('Age', [
                        $form->field(
                                Field::name('age')
                                        ->label('Age')
                                        ->int()
                                        ->required()
                                        ->min($object instanceof Adult ? Person::COMING_OF_AGE : 0)
                                        ->lessThan($object instanceof Child ? Person::COMING_OF_AGE : PHP_INT_MAX)
                        )->bindToProperty(Person::AGE),
                ]);
            }, ['age']);

            $form->dependentOn(['age'], function (CrudFormDefinition $form, array $input, Person $object = null) {
                if ($input['age'] < Person::COMING_OF_AGE || $object instanceof Child) {
                    $form->mapToSubClass(Child::class);
                    $form->section('Kid Details', [
                            $form->field(
                                    Field::name('favourite_colour')
                                            ->label('Favourite Colour')
                                            ->enum(Colour::class, [
                                                    Colour::RED    => 'Red',
                                                    Colour::GREEN  => 'Green',
                                                    Colour::BLUE   => 'Blue',
                                                    Colour::YELLOW => 'Yellow',
                                            ])
                                            ->required()
                            )->bindToProperty(Child::FAVOURITE_COLOUR),
                    ]);
                } else {
                    $form->mapToSubClass(Adult::class);
                    $form->section('Adult Details', [
                            $form->field(Field::name('profession')->label('Profession')->string()->required())
                                    ->bindToProperty(Adult::PROFESSION),
                    ]);
                }
            });
        });


        $module->removeAction()->deleteFromRepository();

        $module->summaryTable(function (SummaryTableDefinition $table) {
            $table->mapCallback(function (Person $person) {
                return $person instanceof Adult ? 'adult' : 'child';
            })->to(Field::name('type')->label('Type')->string()->oneOf(['child' => 'Child', 'adult' => 'Adult']));

            $table->column(Column::name('name')->label('Name')->components([
                    Field::name('first')->label('First Name')->string(),
                    Field::name('last')->label('Last Name')->string(),
            ]));

            $table->mapProperty(Person::FIRST_NAME)->toComponent('name.first');
            $table->mapProperty(Person::LAST_NAME)->toComponent('name.last');
            $table->mapProperty(Person::AGE)->to(Field::name('age')->label('Age')->int());

            $table->view('default', 'Default')
                    ->loadAll()
                    ->asDefault()
                    ->withReorder(function (Person $person, $newIndex) {
                        $elements  = $this->dataSource->getAll();
                        $personKey = array_search($person, $elements);
                        unset($elements[$personKey]);
                        array_splice($elements, $newIndex - 1, 0, [$person]);

                        $this->dataSource->clear();
                        $this->dataSource->saveAll($elements);
                    });

            $table->view('grouped-by-type', 'In Age Groups')
                    ->loadAll()
                    ->groupBy('type');
        });
    }
}