Feature:
    I want to make sure that the owner can view the detail of a provider contribution

    Scenario: As an owner, I can view the detail of a contribution
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/providers/111"
        Then the response status should be 200
        Then I see a text "Provider Company"

    Scenario: A non-existent contribution returns 404
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/providers/does-not-exist"
        Then the response status should be 404

    Scenario: As an owner, I can't view a contribution of another account
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento Ajena" with id "333" of account "777" exists
        Given event carbon footprint with id "999" for event "333" of account "777" with breakdown answered exists
        Given provider "Proveedor Ajeno" with id "444" of account "777" exists
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "777" with token "token-abc" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/providers/111"
        Then the response status should be 403
