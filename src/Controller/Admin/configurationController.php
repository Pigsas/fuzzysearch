<?php

namespace Pigsas\FuzzySearch\Controller\Admin;


use ModuleAdminController;

class configurationController extends ModuleAdminController{

    public function init()
    {
        $this->bootstrap = true;

        $this->fields_options = [
            'general' => [
                'title' => $this->trans('Fuzzy Search Settings', [], 'Modules.Fuzzysearch.Configuration'),
                'fields' => [
                    'FUZZY_SEARCH_FUZZINESS' => [
                        'title' => $this->trans('Use fuzziness', [], 'Modules.Fuzzysearch.Configuration'),
                        'type' => 'bool',
                    ],
                    'FUZZY_SEARCH_PREFIX_LENGTH' => [
                        'title' => $this->trans('Fuzzy search prefix length', [], 'Modules.Fuzzysearch.Configuration'),
                        'type' => 'text',
                    ],
                    'FUZZY_SEARCH_MAX_EXPANSIONS' => [
                        'title' => $this->trans('Fuzzy search max expansions', [], 'Modules.Fuzzysearch.Configuration'),
                        'type' => 'text',
                    ],
                    'FUZZY_SEARCH_DISTANCE' => [
                        'title' => $this->trans('Fuzzy search distance', [], 'Modules.Fuzzysearch.Configuration'),
                        'type' => 'text',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Modules.Fuzzysearch.Configuration'),
                ]
            ]
        ];

        parent::init();
    }
}