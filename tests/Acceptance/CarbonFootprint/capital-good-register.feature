Feature:
    I want to make sure that the software allow to register a capital good

    Scenario: As an admin, I can access to register new capital goods
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/capital-goods/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new capital goods
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/capital-goods/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new capital goods
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/capital-goods/register"
        When I submit a form with "name:Reforma de oficinas|category:renovations|unit:m2" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Bien de capital creado correctamente: Reforma de oficinas"

    Scenario: After register a new capital good, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/capital-goods/register"
        When I submit a form with "name:Reforma de oficinas|category:renovations|unit:m2" and button "Registrar"
        Then I am redirected to route "admin_capital_goods_edit"
