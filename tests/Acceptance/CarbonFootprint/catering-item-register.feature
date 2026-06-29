Feature:
    I want to make sure that the software allow to register a catering item

    Scenario: As an admin, I can access to register new catering items
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/catering-items/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new catering items
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/catering-items/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new catering items
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/catering-items/register"
        When I submit a form with "name:Test Item|category:breakfast|unit:kg" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Elemento de catering creado correctamente: Test Item"

    Scenario: After register a new catering item, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/catering-items/register"
        When I submit a form with "name:Test Item|category:breakfast|unit:kg" and button "Registrar"
        Then I am redirected to route "admin_catering_items_edit"
