async function Ajax(url = '', data = {}, type = 'GET', method = 'fetch'){
  // 整理表单数据
  type = type.toUpperCase()
  let sendData

  if (type == 'GET') {
    let _data = []
    Object.keys(data).forEach(key => {
      _data.push(key + '=' + data[key])
    })
    url = url + '?' + _data.join('&')
  } else {
    sendData = JSON.stringify(data)
  }

  // 创建ajax提交对象

  if (window.fetch && method == 'fetch') {
    let reqConfig = {
      credentials: 'include',
      method: type,
      headers: {
        'Accept': 'application/json',
        'Content-type': 'application/json'
      },
      mode: 'cors',
      cache: 'force-cache'
    }

    try {
      const response = await fetch(url, reqConfig)
      const responseJson = await response.json()

      return responseJson
    } catch(error) {
      throw new Error(error)
    }
  } else {
    return new Promise((resolve, reject) => {
      let reqObj

      if (window.XMLHttpRequest) {
        reqObj = new XMLHttpRequest()
      } else {
        reqObj = new ActiveXObject('Microsoft.XMLHTTP')
      }

      reqObj.open(type, url, true)
      reqObj.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
      reqObj.send(sendData)

      reqObj.onreadystatechange = () => {
        if (reqObj.readyState == 4) {
          if (reqObj.status == 200) {
            let res = reqObj.response
            if (typeof res !== 'object') {
              res = JSON.parse(res)
            }
            resolve(res)
          } else {
            reject(reqObj)
          }
        }
      }
    })
  }

}
