Feature:
    I want to make sure that a provider can navigate and answer the Assembly/Celebration/Desmontaje steps of its public contribution link

    Scenario: A provider can see the assembly step
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/assembly"
        Then the response status should be 200

    Scenario: Submitting the assembly step with no new activities moves on to celebration
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/assembly"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_carbon_footprint_contribution_celebration"

    Scenario: Submitting the breakdown step marks the contribution as answered
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/breakdown"
        When I submit a form with "" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_contribution_answered"
        Then the response status should be 200
        Then I see a text "Gracias"
        Then provider contribution "111" should have status "answered"

    Scenario: An invalid token on the assembly step shows the invalid page
        When I go to "/es/carbon-footprint/contribution/this-token-does-not-exist/assembly"
        Then the response status should be 404

    Scenario: Submitting a phase for a contribution whose event carbon footprint is already closed is refused
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" completed exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/assembly"
        When I submit a form with "" and button "Siguiente"
        Then the response status should be 409
