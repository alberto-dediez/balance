Feature:
    I want to make sure that the software allows to register a category of mitigation measures

    Scenario: As an admin, I can register a new category
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/register"
        Then the response status should be 200
        When I submit a form with "name:Energía" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Categoría de medidas creada correctamente: Energía"

    Scenario: As owner I cannot register categories
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories/register"
        Then the response status should be 403
