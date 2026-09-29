Feature:
    I want to make sure that the software allows to edit a category of mitigation measures

    Scenario: As an admin, I can edit a category
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/edit/333"
        When I submit a form with "name:Energía renovable" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Categoría de medidas actualizada con éxito"
        Then I see a text "Energía renovable"

    Scenario: As owner I cannot edit categories
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given mitigation measure category "Energía" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/edit/333"
        Then the response status should be 403
