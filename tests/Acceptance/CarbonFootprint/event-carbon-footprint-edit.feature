Feature:
    I want to make sure that the system allows to edit a carbon footprint calculation

    Scenario: As an owner, I can edit the carbon footprint of my own event
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint for event "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/edit"
        When I follow redirect
        Then the response status should be 200

    Scenario: As an owner, I can't edit the carbon footprint of events from other accounts
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento Ajeno" with id "333" of account "777" exists
        Given event carbon footprint for event "333" of account "777" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/edit"
        Then the response status should be 403
