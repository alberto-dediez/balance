Feature:
    I want to make sure that the software allows to remove a category of mitigation measures

    Scenario: As an admin, I can remove a category
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la categoría de medidas Energía"

    Scenario: As an admin, I cannot remove a category in use
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "333" exists
        Given mitigation measure "Caldera eficiente" with id "M1" of category "333" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No se ha podido eliminar la categoría de medidas Energía"

    Scenario: As owner I cannot remove categories
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given mitigation measure category "Energía" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/remove/333" with csrf token "remove"
        Then the response status should be 403
