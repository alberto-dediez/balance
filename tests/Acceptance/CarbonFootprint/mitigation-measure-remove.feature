Feature:
    I want to make sure that the software allows to remove a suggested mitigation measure

    Scenario: As an admin, I can remove a measure
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "333" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measures/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la medida Caldera eficiente"

    Scenario: As owner I cannot remove measures
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "333" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measures/remove/333" with csrf token "remove"
        Then the response status should be 403
