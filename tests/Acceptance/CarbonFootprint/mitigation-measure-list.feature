Feature:
    Ability to list the suggested mitigation measures

    Scenario: As admin I can access the list of measures
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "1111" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        Given mitigation measure "Paneles fotovoltaicos" with id "2222" of category "C1" reducing source "building_electricity" between "0.2" and "0.6" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measures"
        Then the response status should be 200
        Then I see a text "Caldera eficiente"
        Then I see a text "Paneles fotovoltaicos"
        Then I see a text "Energía"

    Scenario: As owner I cannot access the list of measures
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measures"
        Then the response status should be 403
