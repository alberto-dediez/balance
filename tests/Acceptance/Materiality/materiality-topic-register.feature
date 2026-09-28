Feature:
    I want to make sure that the software allows to register a materiality topic

    Scenario: As an admin, I can access to register new materiality topics
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new materiality topics
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-topics/register"
        Then the response status should be 403

    Scenario: As an admin, I can register a new materiality topic and I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/register"
        When I submit a form with "name:Lavandería externa|scope:scope_3|direction:upstream|position:20" and button "Registrar"
        Then I am redirected to route "admin_materiality_topics_edit"
        Then the response status should be 200
        Then I see a text "Categoría de materialidad creada correctamente: Lavandería externa"

    Scenario: As an admin, I can't register a scope 3 materiality topic without direction
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/register"
        When I submit a form with "name:Lavandería externa|scope:scope_3|direction:|position:20" and button "Registrar"
        Then the response status should be 200
        Then I see a text "La dirección es obligatoria en las categorías de alcance 3"

    Scenario: As an admin, I can't register a materiality topic with a number already used
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given standard materiality topic "6" with id "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/register"
        When I submit a form with "number:6|name:Viajes|scope:scope_3|direction:upstream|position:6" and button "Registrar"
        Then the response status should be 200
        Then I see a text "Ya existe una categoría con ese número"
