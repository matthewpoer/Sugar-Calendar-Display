require 'net/http'
require 'net/https'
require 'rubygems'
require 'json'
class SugarCRM

  @@restful_path = '/service/v4/rest.php'
  @@use_ssl = false
  @@session = false

  def initialize(url,username,password)
    @url = url
    @username = username
    @password = password

    check_ssl_fix_url()
    test_credentials()
  end

  def check_ssl_fix_url
    if @url.include? 'https://'
      @@use_ssl = true
      @url = @url[8..-1]
    else
      @@use_ssl = false
      @url = @url[7..-1]
    end

    if @url.include? '/'
      # split at the first / and the prefix is the host, suffix goes into @@restful_path
      # I'm pretty sure that Ruby has built-in methods of doing this, but this will work
      # for now I guess
      after = @url.split('/',2)
      @url = after[0]
      the_rest = after[1]
      @@restful_path = '/' + after[1] + @@restful_path
    end
  end

  def test_credentials
    params = {'application' => 'SugarCRM Rest Connector','user_auth' => {'user_name'=>@username,'password'=>@password,'version'=>"1.0"}}
    resp = call('login',params)
    @@session = resp["id"]
  end

  def call(rest_method,params)
    if @@use_ssl
      http = Net::HTTP.new(@url, 443)
      http.use_ssl = true
    else
      http = Net::HTTP.new(@url, 80)
    end
    arguments = "method=#{rest_method}&input_type=json&response_type=json&rest_data=#{params.to_json}"
    response = http.post @@restful_path, arguments
    resp = JSON.parse(response.body)    
    if resp.has_key?("number")
      raise "Error from SugarCRM Server!\nName: " + resp['name'] + "\nDescription: " + resp['description']
    end
    return resp
  end
  
end
