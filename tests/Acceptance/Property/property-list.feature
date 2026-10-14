Feature:
        Ability to list properties in web

        Scenario: As admin I can access a list of all properties
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given property "Hotel" with id "1111" exists
                Given property "Rural" with id "2222" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/admin/properties"
                Then the response status should be 200
                Then I see a table with 4 rows

        Scenario: As normal user I can't access a list of all properties
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given user "user1@dediez.software" with role "ROLE_USER" exists
                Given property "Hotel" with id "1111" exists
                Given property "Rural" with id "2222" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/admin/properties"
                Then the response status should be 403

        Scenario: As owner I can access a list of all properties of my account
                Given account "MyCompany" with id "666" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
                Given property "Hotel" with id "1111" of account "666" exists
                Given property "Rural" with id "2222" of account "666" exists
                Given property "Playa" with id "3333" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/properties"
                Then the response status should be 200
                Then I see a table with 4 rows

      Scenario: As an authenticated owner, I can list my properties via API
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
                Given property "Hotel" with id "111" of account "mycompany" exists
                Given property "Rural" with id "222" of account "mycompany" exists
                When I am authenticated in API as "owner@dediez.software"
                And I send a GET request to "/api/es/properties"
                Then the status code should be 200
                And the response JSON should be equal to:
                """
                [
                    {"property_id": "111", "name": "Hotel", "account_id": "mycompany", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null},
                    {"property_id": "222", "name": "Rural", "account_id": "mycompany", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null}
                ]
                """

      Scenario: As an authenticated owner, I only see properties of my own account via API
            Given account "MyCompany" with id "mycompany" exists
            Given account "OtherCompany" with id "othercompany" exists
            Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
            Given property "Hotel" with id "111" of account "mycompany" exists
            Given property "Other" with id "222" of account "othercompany" exists
            When I am authenticated in API as "owner@dediez.software"
            And I send a GET request to "/api/es/properties"
            Then the status code should be 200
            And the response JSON should be equal to:
            """
            [
                {"property_id": "111", "name": "Hotel", "account_id": "mycompany", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null}
            ]
            """

      Scenario: As admin I can list properties of any account via API passing account_id
            Given account "MyCompany" with id "mycompany" exists
            Given account "OtherCompany" with id "othercompany" exists
            Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
            Given property "Hotel" with id "111" of account "mycompany" exists
            Given property "Other" with id "222" of account "othercompany" exists
            When I am authenticated in API as "admin@dediez.software"
            And I send a GET request to "/api/es/properties?account_id=mycompany"
            Then the status code should be 200
            And the response JSON should be equal to:
            """
            [
                {"property_id": "111", "name": "Hotel", "account_id": "mycompany", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null}
            ]
            """

      Scenario: Not authenticated user cannot list properties via API
            When I send a GET request to "/api/es/properties"
            Then the status code should be 403
