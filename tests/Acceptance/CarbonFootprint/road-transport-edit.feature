Feature:
    I want to make sure that the system is allow to access the details of a road transport

    Scenario: As an admin, I can access to edit road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given road transport "Road Transport 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports/edit/333"
        When I submit a form with "name:A new name|unit:ud|vehicle:vans" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Transporte por carretera actualizado con éxito"

    Scenario: As an admin, I see the emission factors table when editing a road transport
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given road transport "Road Transport 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"

    Scenario: As an owner, I can't access to edit road transports
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given road transport "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/road-transports/edit/333"
        Then the response status should be 403