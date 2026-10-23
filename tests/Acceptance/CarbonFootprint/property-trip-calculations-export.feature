Feature:
    Ability to export the trip calculations of a property as spreadsheet files

    Scenario: As an owner I can export the calculations of a property of my account
        Given account "Mi cuenta" with id "666" exists
        Given account "Otra cuenta" with id "999" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "777" of account "666" exists
        Given property "Otro hotel" with id "888" of account "999" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property_trip_calculations/export?propertyId=777"
        Then the response status should be 200
        Then I see an option "777" in form field "[filter][propertyId]"
        Then I do not see an option "888" in form field "[filter][propertyId]"
        When I submit a form with "[filter][propertyId]:777" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As an admin I can export the calculations of a property of another account
        Given account "Admins" with id "555" exists
        Given account "Mi cuenta" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "555" exists
        Given property "Hotel" with id "777" of account "666" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/property_trip_calculations/export?propertyId=777"
        Then the response status should be 200
        Then I see a form field "[filter][propertyId]" with value "777"
        When I submit a form with "[filter][propertyId]:777" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As a normal user I cannot access the property calculations export
        Given account "Mi cuenta" with id "666" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/property_trip_calculations/export"
        Then the response status should be 403
