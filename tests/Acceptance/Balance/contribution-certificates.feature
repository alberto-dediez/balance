Feature:
    Ability for a contributor to obtain the certificate of their contribution and for anybody to verify it

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists

    Scenario: Paying an offsetting contribution issues a compensation certificate traced to its credit lot
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then contribution "C1" should be "paid"
        Then contribution "C1" should consume "2" tonnes of credit purchase "2222"
        When the queued messages are processed
        Then the certificate of contribution "C1" should be a "compensation" document
        Then the certificate of contribution "C1" should be "generated"

    Scenario: Paying a social contribution issues a document that never mentions offsetting
        Given social project "Escuela rural" with id "3333" and price per tonne "20" exists
        Given project "3333" is adhered to account "666"
        Given contribution "C1" of account "666" for project "3333" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the queued messages are processed
        Then the certificate of contribution "C1" should be a "social_contribution" document
        Then the certificate of contribution "C1" should be "generated"
        Then contribution "C1" should consume no credit

    Scenario: The contributor downloads the certificate from the thank-you page
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the queued messages are processed
        When I go to "/es/carbon-footprint/property-trip/token-abc/contribution/C1/return"
        Then the response status should be 200
        Then I see a text "Descargar certificado"
        When I download the certificate of contribution "C1" with token "token-abc"
        Then the response status should be 200

    Scenario: The certificate cannot be downloaded with an invalid link token
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the queued messages are processed
        When I download the certificate of contribution "C1" with token "not-a-token"
        Then the response status should be 404

    Scenario: The thank-you page announces the certificate while it is still being drawn
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I go to "/es/carbon-footprint/property-trip/token-abc/contribution/C1/return"
        Then the response status should be 200
        Then I see a text "Estamos preparando tu certificado"
        Then I do not see a text "Descargar certificado"

    Scenario: Anybody can verify a compensation certificate against the document in their hands
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the queued messages are processed
        When I go to the verification page of the certificate of contribution "C1"
        Then the response status should be 200
        Then I see a text "Certificado válido"
        Then I see a text "Reforestación"
        Then I see a text "REF-1"
        Then I see a text "Guest"
        Then I see a text "0,000000"
        Then I see a text "2,000000"
        Then I do not see a text "guest@example.com"

    Scenario: The reference printed on the certificate does not open the verification page
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the queued messages are processed
        When I go to the verification page of the certificate of contribution "C1" using its printed reference
        Then the response status should be 404

    Scenario: An unknown verification token is not found
        When I go to "/es/certificate/ZZZZZZZZZZZZZZZZZZZZZZZZ"
        Then the response status should be 404
