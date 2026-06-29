Feature:
    I want to make sure that the software allow to register a road transport

    Scenario: As an admin, I can access to register new road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/road-transports/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports/register"
        When I submit a form with "name:Test Road Transport|unit:kg|vehicle:trucks" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Transporte por carretera creado correctamente: Test Road Transport"

    Scenario: After register a new road transport, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports/register"
        When I submit a form with "name:Test Road Transport|unit:kg|vehicle:trucks" and button "Registrar"
        Then I am redirected to route "admin_road_transports_edit"