Feature:
    I want to make sure that the system is allow to access the details of a property

    Scenario: As an admin, I can access to edit properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given property "Hotel" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties/edit/333"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal property I can't edit properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given property "Hotel" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/properties/edit/333"
        Then the response status should be 403

    Scenario: As an admin, I can edit a property
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "OneAccount" with id "666" exists
        Given property "Hotel" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties/edit/333"
        When I submit a form with "name:OtherHotel" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Propiedad actualizada"

    Scenario: As an admin, I can edit a property and exit
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "OneAccount" with id "666" exists
        Given property "Hotel" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties/edit/333"
        When I submit a form with "name:OtherHotel" and button "Guardar y salir"
        Then I am redirected to "/es/admin/properties" 

    Scenario: As an owner, I can access to edit properties of my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties/edit/333"
        When I submit a form with "name:OtherHotel" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Propiedad actualizada"

    Scenario: As an owner, I can set the exact location of my property and it replaces its coordinates
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given place "place-ref" named "Hotel Ritz, Madrid" at "40.4180", "-3.6940" exists
        Given property "Hotel" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties/edit/333"
        When I pick "place-ref" in the autocomplete "placeReference"
        When I submit a form with "name:Hotel|[info][placeReference]:place-ref|[info][latitude]:1|[info][longitude]:2" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then property "333" has place location "Hotel Ritz, Madrid" at "40.4180", "-3.6940"

    Scenario: As an owner, I can't access to edit properties of other accounts
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "333" of account "111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties/edit/333"
        Then the response status should be 403