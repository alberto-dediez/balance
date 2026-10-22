Feature:
    I want to make sure that the software allows to edit a suggested mitigation measure

    Scenario: As an admin, I can edit a measure
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "333" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measures/edit/333"
        When I submit a form with "name:Caldera de condensación|[effects][0][maximumReduction]:40" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Medida actualizada con éxito"
        Then I see a text "Caldera de condensación"
        Then I see a text "40,00 %"

    Scenario: As an admin, I can make a measure reduce a scope 3 source
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Inversión" with id "C1" exists
        Given mitigation measure "Inversión sostenible" with id "333" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measures/edit/333"
        When I submit a form with "[effects][0][source]:investments" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Medida actualizada con éxito"
        Then mitigation measure "333" should reduce source "investments"

    Scenario: As owner I cannot edit measures
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "333" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measures/edit/333"
        Then the response status should be 403
